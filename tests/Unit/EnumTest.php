<?php
namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\BatchStatus;
use App\Enums\BlockchainIdentityStatus;
use App\Enums\DecisionType;
use App\Enums\LookupResult;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\ProductApprovalStatus;
use App\Enums\QRType;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\TransferStatus;
use App\Enums\UnitStatus;
use PHPUnit\Framework\Attributes\Test;

class EnumTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function it_round_trips_enum_values(): void
    {
        $this->assertSame('PENDING', AccountStatus::pending->value);
        $this->assertSame('CREATED', BatchStatus::created->value);
        $this->assertSame('ACTIVE', BlockchainIdentityStatus::active->value);
        $this->assertSame('THU_HOI', DecisionType::thuHoi->value);
        $this->assertSame('VALID', LookupResult::valid->value);
        $this->assertSame('DA_DUYET', OrganizationApprovalStatus::daDuyet->value);
        $this->assertSame('NHA_SAN_XUAT', OrganizationType::nhaSanXuat->value);
        $this->assertSame('DA_DUYET', ProductApprovalStatus::daDuyet->value);
        $this->assertSame('BATCH', QRType::batch->value);
        $this->assertSame('APPROVED', ReportStatus::approved->value);
        $this->assertSame('QUAN_TRI_VIEN', Role::quanTriVien->value);
        $this->assertSame('IN_TRANSIT', TransferStatus::inTransit->value);
        $this->assertSame('AVAILABLE', UnitStatus::available->value);
    }
}