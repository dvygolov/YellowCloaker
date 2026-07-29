#!/usr/bin/env python3
"""Merge k6 and ywbtest telemetry into portable JSON/CSV/Markdown reports."""

from __future__ import annotations

import argparse
import csv
import json
from pathlib import Path
from typing import Any


def load_json(path: Path) -> dict[str, Any]:
    with path.open(encoding="utf-8") as handle:
        return json.load(handle)


def flatten(prefix: str, value: Any, target: dict[str, Any]) -> None:
    if isinstance(value, dict):
        for key, child in value.items():
            flatten(f"{prefix}{key}_", child, target)
        return
    target[prefix[:-1]] = value


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("results_root", type=Path)
    args = parser.parse_args()
    root = args.results_root.resolve()
    target_root = root / "target"
    generator_root = root / "generator"

    target_files = {
        path.name.removesuffix(".target-summary.json"): path
        for path in target_root.rglob("*.target-summary.json")
    }
    generator_files = {
        path.name.removesuffix(".generator-summary.json"): path
        for path in generator_root.rglob("*.generator-summary.json")
    }
    config_files = {
        path.name.removesuffix(".config.json"): path
        for path in target_root.rglob("*.config.json")
    }

    cases = sorted(set(target_files) | set(generator_files))
    merged: list[dict[str, Any]] = []
    for name in cases:
        row: dict[str, Any] = {"case": name}
        if name in config_files:
            flatten("config_", load_json(config_files[name]), row)
        if name in generator_files:
            flatten("client_", load_json(generator_files[name]), row)
        if name in target_files:
            flatten("server_", load_json(target_files[name]), row)
        merged.append(row)

    json_path = root / "aggregate.json"
    csv_path = root / "aggregate.csv"
    markdown_path = root / "summary.md"
    json_path.write_text(json.dumps(merged, ensure_ascii=False, indent=2), encoding="utf-8")

    fields = sorted({key for row in merged for key in row}, key=lambda key: (key != "case", key))
    with csv_path.open("w", newline="", encoding="utf-8-sig") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields)
        writer.writeheader()
        writer.writerows(merged)

    def fmt(value: Any, digits: int = 1) -> str:
        if value is None or value == "":
            return "—"
        if isinstance(value, float):
            return f"{value:.{digits}f}"
        return str(value)

    lines = [
        "# YellowTDS ywbtest load-test metrics",
        "",
        "Generated from the preserved k6, nginx, FPM, vmstat and iostat artifacts.",
        "Client metrics are authoritative for achieved RPS and dropped iterations; nginx upstream metrics isolate PHP processing time.",
        "",
        "| Case | Requested RPS | Achieved RPS | Dropped | Errors | Client p95 ms | Client p99 ms | Upstream p95 ms | CPU avg % | Steal avg % |",
        "|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|",
    ]
    for row in merged:
        lines.append(
            "| " + " | ".join([
                row["case"],
                fmt(row.get("client_requested_rps")),
                fmt(row.get("client_achieved_rps"), 2),
                fmt(row.get("client_dropped_iterations"), 0),
                fmt(row.get("client_http_failed_rate"), 4),
                fmt(row.get("client_p95_ms")),
                fmt(row.get("client_p99_ms")),
                fmt(row.get("server_upstream_time_p95_ms")),
                fmt(row.get("server_target_cpu_avg_percent")),
                fmt(row.get("server_target_steal_avg_percent")),
            ]) + " |"
        )
    markdown_path.write_text("\n".join(lines) + "\n", encoding="utf-8")

    print(json.dumps({
        "cases": len(merged),
        "with_client": len(generator_files),
        "with_server": len(target_files),
        "json": str(json_path),
        "csv": str(csv_path),
        "markdown": str(markdown_path),
    }, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
