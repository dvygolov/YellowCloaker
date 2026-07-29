<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/admin/campaignvalidation.php';

final class CampaignConversionValidationTest extends TestCase
{
    public function testTransactionIdParameterStringIsSavedAsCanonicalArray(): void
    {
        $input = $this->conversionInput(' tid, order_id, TID, transaction_id, ');

        $this->assertNull(normalize_conversion_input($input));
        $this->assertSame(
            ['tid', 'order_id', 'transaction_id'],
            $input['conversions']['deduplication']['transaction_id_parameters']
        );
    }

    public function testMissingTransactionIdParameterSettingUsesTidDefault(): void
    {
        $input = $this->conversionInput(null);
        unset($input['conversions']['deduplication']['transaction_id_parameters']);

        $this->assertNull(normalize_conversion_input($input));
        $this->assertSame(
            ['tid'],
            $input['conversions']['deduplication']['transaction_id_parameters']
        );
    }

    public function testInvalidOrEmptyTransactionIdParameterSettingIsRejected(): void
    {
        foreach ([null, '', 'status', 'bad parameter'] as $invalid) {
            $input = $this->conversionInput($invalid);
            $this->assertNotNull(normalize_conversion_input($input));
        }
    }

    private function conversionInput(mixed $transactionIdParameters): array
    {
        return [
            'conversions' => [
                'statuses' => array_map(
                    static fn(ConversionStatus $status): array => $status->jsonSerialize(),
                    ConversionSettings::defaultStatuses()
                ),
                'deduplication' => [
                    'enabled' => true,
                    'transaction_id_parameters' => $transactionIdParameters,
                    'paid_repeat_without_tid' => 'reject',
                ],
                'form' => ['enabled' => false, 'status' => 'Lead'],
                'site' => ['enabled' => false],
            ],
        ];
    }
}
