<?php
namespace Tests\Unit;

use App\Services\Blockchain\ChainId;
use PHPUnit\Framework\Attributes\Test;

class ChainIdTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function it_generates_a_chain_id_from_business_value(): void
    {
        $value = ChainId::tu('test-business-value');

        $this->assertMatchesRegularExpression('/^0x[0-9a-f]{16}$/', $value);
    }
}