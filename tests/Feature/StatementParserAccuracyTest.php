<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BankImportController;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Parser accuracy on sample statements: the bank CSV parser must extract
 * date / description / amount / type correctly from real statement layouts.
 * Exercises the protected parseCsvFile against fixture files under
 * tests/Fixtures/statements.
 */
class StatementParserAccuracyTest extends TestCase
{
    private function parse(string $fixture, ?string $format): array
    {
        $controller = new BankImportController();
        $method = new ReflectionMethod($controller, 'parseCsvFile');
        $method->setAccessible(true);

        return $method->invoke($controller, base_path("tests/Fixtures/statements/{$fixture}"), $format);
    }

    /** @test */
    public function it_parses_a_bank_of_the_west_statement(): void
    {
        $rows = $this->parse('bank_of_the_west_sample.csv', 'bank_west');

        $this->assertCount(3, $rows);

        // Debit line (Debit column populated).
        $this->assertSame('2026-01-30', $rows[0]['transaction_date']);
        $this->assertSame('SAMS CLUB #4160', $rows[0]['description']);
        $this->assertSame('debit', $rows[0]['transaction_type']);
        $this->assertEqualsWithDelta(45.20, $rows[0]['amount'], 0.001);

        // Credit line (Credit column populated).
        $this->assertSame('2026-01-31', $rows[1]['transaction_date']);
        $this->assertSame('credit', $rows[1]['transaction_type']);
        $this->assertEqualsWithDelta(1500.00, $rows[1]['amount'], 0.001);

        // Row with a check reference keeps it and still finds the merchant text.
        $this->assertStringContainsString('SYSCO FOOD SERVICE', $rows[2]['description']);
        $this->assertSame('1001', $rows[2]['reference_number']);
        $this->assertSame('debit', $rows[2]['transaction_type']);
    }

    /** @test */
    public function it_parses_a_generic_statement_and_derives_type_from_sign(): void
    {
        $rows = $this->parse('generic_bank_sample.csv', 'generic');

        $this->assertCount(2, $rows);

        // Negative amount => debit; amount stored as absolute value.
        $this->assertSame('2026-01-30', $rows[0]['transaction_date']);
        $this->assertSame('SHELL GAS STATION', $rows[0]['description']);
        $this->assertSame('debit', $rows[0]['transaction_type']);
        $this->assertEqualsWithDelta(45.20, $rows[0]['amount'], 0.001);

        // Positive amount => credit.
        $this->assertSame('SQUARE INC DEPOSIT', $rows[1]['description']);
        $this->assertSame('credit', $rows[1]['transaction_type']);
        $this->assertEqualsWithDelta(1500.00, $rows[1]['amount'], 0.001);
    }
}
