// ── Helper: get flow distribution value for a given flow index ──
export function getFlowDist(fi) {
    var sel = document.querySelector('.flow-dist[data-fi="' + fi + '"]');
    return sel ? sel.value : 'equal';
}

// ── Helper: check if weights look like equal distribution (differ by at most 1) ──
export function looksEqualDistributed(weights) {
    if (weights.length === 0) return true;
    var min = Math.min.apply(null, weights);
    var max = Math.max.apply(null, weights);
    return (max - min) <= 1 && weights.reduce(function(a, b) { return a + b; }, 0) === 100;
}

// ── Helper: distribute 100 equally among count items (largest remainder) ──
export function equalWeights(count) {
    if (count <= 0) return [];
    var base = Math.floor(100 / count);
    var remainder = 100 - base * count;
    var result = [];
    for (var i = 0; i < count; i++) {
        result.push(base + (i < remainder ? 1 : 0));
    }
    return result;
}

// ── Helper: redistribute weights for a set of weight inputs after add/remove ──
export function redistributeWeights(weightInputs) {
    var count = weightInputs.length;
    if (count === 0) return;
    // Read current weights
    var current = [];
    for (var i = 0; i < count; i++) {
        current.push(parseInt(weightInputs[i].value, 10) || 0);
    }
    // Check if all existing weights (excluding last which is the new empty one) look equal
    var existing = current.slice(0, count - 1);
    var shouldRedistribute = existing.length === 0 || looksEqualDistributed(existing);
    if (shouldRedistribute) {
        var newWeights = equalWeights(count);
        for (var j = 0; j < count; j++) {
            weightInputs[j].value = newWeights[j];
        }
    }
}

// ── Helper: redistribute weights after an item is removed ──
export function redistributeWeightsAfterDelete(weightInputs, removedWeight) {
    var count = weightInputs.length;
    if (count === 0) return;
    var current = [];
    for (var i = 0; i < count; i++) {
        current.push(parseInt(weightInputs[i].value, 10) || 0);
    }
    // If remaining weights + removed weight looked equal, redistribute
    var withRemoved = current.concat([removedWeight]);
    if (looksEqualDistributed(withRemoved)) {
        var newWeights = equalWeights(count);
        for (var j = 0; j < count; j++) {
            weightInputs[j].value = newWeights[j];
        }
    }
}
