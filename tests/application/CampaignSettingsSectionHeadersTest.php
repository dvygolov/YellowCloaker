<?php

use PHPUnit\Framework\TestCase;

final class CampaignSettingsSectionHeadersTest extends TestCase
{
    public function testPrimaryCampaignSectionsUseConsistentHeadings(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $sections = [
            'sec-domains' => ['bi-globe2', 'Domains', 'Add the domains that route traffic through this campaign.'],
            'sec-safepage' => ['bi-shield-check', 'Safe Page', 'Choose which visitors see the safe page and how it is served.'],
            'sec-flows' => ['bi-diagram-3', 'Flows', 'Create and prioritize the routes and funnel steps used by this campaign.'],
            'sec-scripts' => ['bi-code-slash', 'Scripts', 'Configure browser-side behavior for landing pages and funnel transitions.'],
            'sec-events' => ['bi-activity', 'Events', 'Choose which landing interactions and performance metrics this campaign records.'],
            'sec-misc' => ['bi-sliders', 'Misc', 'Configure campaign-wide uniqueness and reporting settings.'],
            'sec-conversions' => ['bi-bullseye', 'Conversions', 'Define conversion statuses, deduplication, and on-site tracking.'],
            'sec-postbacks' => ['bi-arrow-left-right', 'Postbacks', 'Receive conversion updates and send them to external services.'],
            'sec-api' => ['bi-plug', 'Integration', 'Choose how an external website connects to this campaign.'],
        ];

        foreach ($sections as $sectionId => [$icon, $title, $description]) {
            $sectionStart = strpos($form, 'id="' . $sectionId . '"');
            $this->assertNotFalse($sectionStart, "Missing section {$sectionId}");
            $sectionEnd = strpos($form, '</section>', $sectionStart);
            $this->assertNotFalse($sectionEnd, "Unclosed section {$sectionId}");
            $section = substr($form, $sectionStart, $sectionEnd - $sectionStart);

            $this->assertStringContainsString('class="campaign-section-heading"', $section);
            $this->assertStringContainsString('class="bi ' . $icon . '"', $section);
            $this->assertStringContainsString('> ' . $title . '</h3>', $section);
            $this->assertStringContainsString('<p>' . $description . '</p>', $section);
        }
    }

    public function testPrimarySidebarUsesCompactIconsAndRequestedOrder(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $navStart = strpos($form, '<nav class="camp-sidebar"');
        $navEnd = strpos($form, '</nav>', $navStart);

        $this->assertNotFalse($navStart);
        $this->assertNotFalse($navEnd);
        $nav = substr($form, $navStart, $navEnd - $navStart);

        $items = [
            '#sec-domains' => 'bi-globe2',
            '#sec-safepage' => 'bi-shield-check',
            '#sec-flows' => 'bi-diagram-3',
            '#sec-conversions' => 'bi-bullseye',
            '#sec-events' => 'bi-activity',
            '#sec-misc' => 'bi-sliders',
            '#sec-postbacks' => 'bi-arrow-left-right',
            '#sec-api' => 'bi-plug',
            '#sec-scripts' => 'bi-code-slash',
        ];

        $previousPosition = -1;
        foreach ($items as $href => $icon) {
            $position = strpos($nav, 'href="' . $href . '"');
            $this->assertNotFalse($position, "Missing sidebar item {$href}");
            $this->assertGreaterThan($previousPosition, $position, "Wrong sidebar order for {$href}");
            $this->assertMatchesRegularExpression(
                '/href="' . preg_quote($href, '/') . '"[^>]*>[^<]*<i class="bi ' . preg_quote($icon, '/') . ' campaign-nav-icon" aria-hidden="true"><\/i>/',
                $nav
            );
            $previousPosition = $position;
        }

        $this->assertStringNotContainsString(
            'step-nav-item" data-flow-index="<?= $fi ?>" data-step-index="<?= $si ?>"><a href="#sec-step-<?= $fi ?>-<?= $si ?>"><i',
            $nav
        );
    }

    public function testEventsHeadingHasNoPromotionalBadgeOrIncorrectTimingCopy(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');

        $this->assertStringNotContainsString('events-section-badge', $form);
        $this->assertStringNotContainsString('Real User Monitoring</span>', $form);
        $this->assertStringNotContainsString(
            'Event values are elapsed time from tracker initialization',
            $form
        );
    }

    public function testMiscUsesOneVisibleUniquenessTitleAndOffsetTimezoneLabels(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $sectionStart = strpos($form, 'id="sec-misc"');
        $sectionEnd = strpos($form, 'id="sec-conversions"', $sectionStart);

        $this->assertNotFalse($sectionStart);
        $this->assertNotFalse($sectionEnd);

        $section = substr($form, $sectionStart, $sectionEnd - $sectionStart);

        $this->assertSame(1, substr_count($section, '>Uniqueness counting</span>'));
        $this->assertStringContainsString('flow-group-title flow-group-title-with-help', $section);
        $this->assertStringContainsString('campaign-setting-row-control-only', $section);
        $this->assertStringContainsString('name="statistics.timezone"', $section);
        $this->assertStringContainsString(
            'get_timezone_option_label($timezoneId, $timezoneLabelDate)',
            $section
        );
    }

    public function testConversionDeduplicationUsesTooltipsAndEditableParameterList(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $sectionStart = strpos($form, 'id="sec-conversions"');
        $sectionEnd = strpos($form, 'id="sec-postbacks"', $sectionStart);

        $this->assertNotFalse($sectionStart);
        $this->assertNotFalse($sectionEnd);
        $section = substr($form, $sectionStart, $sectionEnd - $sectionStart);

        $this->assertStringContainsString(
            'name="conversions.deduplication.transaction_id_parameters"',
            $section
        );
        $this->assertStringContainsString('>Transaction ID parameters</label>', $section);
        $this->assertStringContainsString('placeholder="tid, transaction_id, order_id"', $section);
        $this->assertStringNotContainsString('id="conversion-tid-parameters" type="text" readonly', $section);
        $this->assertStringContainsString(
            'One transaction ID is one immutable transaction within each configured parameter.',
            $section
        );
        $this->assertStringContainsString(
            'Used only while Transaction ID deduplication is Off.',
            $section
        );
        $this->assertStringContainsString(
            'id="conversion-repeat-mode-value" name="conversions.deduplication.paid_repeat_without_tid"',
            $section
        );
        $this->assertStringNotContainsString(
            'id="conversion-repeat-mode" name="conversions.deduplication.paid_repeat_without_tid"',
            $section
        );
        $this->assertStringNotContainsString(
            '<small>One tid is one immutable transaction.',
            $section
        );
        $this->assertStringNotContainsString(
            '<small>Used only while Transaction ID deduplication is disabled.',
            $section
        );
    }

    public function testConversionFormAndSiteDescriptionsUseAccessibleTooltips(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $sectionStart = strpos($form, 'id="sec-conversions"');
        $sectionEnd = strpos($form, 'id="sec-postbacks"', $sectionStart);

        $this->assertNotFalse($sectionStart);
        $this->assertNotFalse($sectionEnd);
        $section = substr($form, $sectionStart, $sectionEnd - $sectionStart);

        $descriptions = [
            'Creates a zero-payout record with source form_submit.',
            'Accepts internal names and aliases using the current clickid. Payout is not accepted.',
        ];

        foreach ($descriptions as $description) {
            $this->assertStringContainsString(
                'class="bi bi-info-circle admin-info-icon setting-help-icon"',
                $section
            );
            $this->assertStringContainsString(
                'tabindex="0" role="img" aria-label="' . $description . '" data-tooltip="' . $description . '"',
                $section
            );
        }

        $this->assertStringNotContainsString(
            '<small>Creates a zero-payout record',
            $section
        );
        $this->assertStringNotContainsString(
            '<small>Accepts internal names and aliases',
            $section
        );
        $this->assertStringContainsString(
            'aria-label="Create conversion after successful form submit"',
            $section
        );
        $this->assertStringContainsString(
            'aria-controls="conversion-form-settings"',
            $section
        );
        $this->assertStringContainsString(
            'aria-label="Enable website status tracking"',
            $section
        );
    }

    public function testNewConversionStatusUsesCustomLabelAndStandardTrashIcon(): void
    {
        $script = file_get_contents(__DIR__ . '/../../code/admin/js/campsettings/conversions.js');

        $this->assertStringContainsString(
            '<span class="conversion-status-kind">Custom</span>',
            $script
        );
        $this->assertStringContainsString(
            'title="Delete custom status" aria-label="Delete custom status"',
            $script
        );
        $this->assertStringContainsString(
            '<i class="bi bi-trash" aria-hidden="true"></i>',
            $script
        );
        $this->assertStringNotContainsString('New status', $script);
        $this->assertStringNotContainsString('bi-x-lg', $script);
    }

    public function testPostbackExampleTracksTheFirstConfiguredTransactionIdParameter(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $script = file_get_contents(__DIR__ . '/../../code/admin/js/campsettings/conversions.js');

        $this->assertStringContainsString('id="postback-url-example"', $form);
        $this->assertStringContainsString('data-url-prefix=', $form);
        $this->assertStringContainsString('syncPostbackUrlExample', $script);
        $this->assertStringContainsString('addEventListener(\'input\', syncPostbackUrlExample)', $script);
    }

    public function testPostbackProtectionDescriptionsUseAccessibleTooltips(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $sectionStart = strpos($form, 'id="sec-postbacks"');
        $sectionEnd = strpos($form, 'id="sec-api"', $sectionStart);

        $this->assertNotFalse($sectionStart);
        $this->assertNotFalse($sectionEnd);
        $section = substr($form, $sectionStart, $sectionEnd - $sectionStart);

        $this->assertStringContainsString('<span>Key protection</span>', $section);
        $this->assertStringContainsString(
            'every incoming postback must include the pbkey parameter',
            $section
        );
        $this->assertStringContainsString(
            '<label for="postback-pbkey-values">Allowed key values</label>',
            $section
        );
        $this->assertStringContainsString(
            'Matching is exact and case-sensitive',
            $section
        );
        $this->assertStringContainsString('aria-label="Enable key protection"', $section);
        $this->assertStringContainsString('aria-controls="postback-pbkey-settings"', $section);
        $this->assertStringNotContainsString('<span>pbkey protection</span>', $section);
        $this->assertStringNotContainsString('<span>Allowed pbkey values</span>', $section);
        $this->assertStringNotContainsString('Separate multiple keys with commas.</small>', $section);
    }

    public function testS2sUsesTemplateBasedSearchableStatusAndEventChipFields(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $script = file_get_contents(__DIR__ . '/../../code/admin/js/campsettings/postbacks.js');

        $this->assertStringContainsString('<template id="s2s-rule-template">', $form);
        $this->assertStringContainsString('data-s2s-chip-field="statuses"', $form);
        $this->assertStringContainsString('data-s2s-chip-field="events"', $form);
        $this->assertSame(2, substr_count($form, 'role="combobox"'));
        $this->assertSame(2, substr_count($form, 'role="listbox"'));
        $this->assertStringContainsString('Performance metrics are excluded.', $form);
        $this->assertStringContainsString('id="s2s-initial-data"', $form);
        $this->assertStringContainsString('js/campsettings/postbacks.js', $form);
        $this->assertStringNotContainsString(
            'Events for which S2S-postback will be sent:',
            $form
        );
        $this->assertStringNotContainsString("$('#add-s2s-item').cloneData", $form);

        $this->assertStringContainsString(
            '`postback.s2s[${index}][statuses][]`',
            $script
        );
        $this->assertStringContainsString(
            '`postback.s2s[${index}][events][]`',
            $script
        );
        $this->assertStringContainsString('role', $script);
        $this->assertStringContainsString('aria-activedescendant', $script);
        $this->assertStringContainsString('ordinaryEventCatalogFromState', $script);
    }
}
