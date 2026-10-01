<?php
namespace App\Enums;

enum Role: string
{
    case nhaSanXuat = 'NHA_SAN_XUAT';
    case nhaPhanPhoi = 'NHA_PHAN_PHOI';
    case nhaThuoc = 'NHA_THUOC';
    case coQuanQuanLy = 'CO_QUAN_QUAN_LY';
    case quanTriVien = 'QUAN_TRI_VIEN';
}