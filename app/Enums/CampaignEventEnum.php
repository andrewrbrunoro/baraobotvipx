<?php declare(strict_types=1);

namespace App\Enums;

enum CampaignEventEnum: string
{
    case ORDER_SUCCESS = 'ORDER_SUCCESS';
    case ORDER_FAIL = 'ORDER_FAIL';
    case MEMBERSHIP_EXPIRED = 'MEMBERSHIP_EXPIRED';
    case MEMBERSHIP_EXPIRING = 'MEMBERSHIP_EXPIRING';
    case MEMBERSHIP_CREATED = 'MEMBERSHIP_CREATED';
    case MEMBERSHIP_RENEWED = 'MEMBERSHIP_RENEWED';
}
