<style>
    @page {
        margin: 18px 22px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10px;
        color: #111827;
        background: #fff;
    }

    .letterhead {
        width: 100%;
        border: 1px solid #b89f7c;
        margin-bottom: 14px;
    }

    .letterhead-tsaubatul {
        width: 100%;
        margin-bottom: 14px;
        padding: 12px 10px 10px;
        border-bottom: 2px solid #111;
        color: #111;
    }

    .letterhead-tsaubatul-table {
        width: 100%;
        border-collapse: collapse;
    }

    .letterhead-tsaubatul-table td {
        border: none;
        padding: 0;
        vertical-align: middle;
    }

    .tsaubatul-text {
        text-align: center;
    }

    .tsaubatul-name {
        font-size: 28px;
        font-weight: 500;
        letter-spacing: 1px;
        line-height: 1.15;
    }

    .tsaubatul-address {
        margin-top: 8px;
        font-size: 16px;
        line-height: 1.25;
    }

    .tsaubatul-meta {
        margin-top: 5px;
        font-size: 13px;
        line-height: 1.25;
    }

    .letterhead-top {
        background: #642400;
        color: #fff;
        padding: 11px 18px 10px;
        border-bottom: 4px solid #d9c3a2;
    }

    .letterhead-top-table,
    .letterhead-main-table {
        width: 100%;
        border-collapse: collapse;
    }

    .letterhead-top-table td,
    .letterhead-main-table td {
        border: none;
        padding: 0;
        vertical-align: middle;
    }

    .logo-cell {
        width: 105px;
        text-align: center;
    }

    .logo-box {
        width: 78px;
        height: 78px;
        margin: 0 auto;
        border: 3px solid #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 72px;
        font-size: 13px;
        font-weight: bold;
        color: #fff;
    }

    .logo-star {
        border-radius: 14px;
        transform: rotate(45deg);
    }

    .logo-star span {
        display: block;
        transform: rotate(-45deg);
    }

    .qiraati-logo {
        width: 150px;
        height: 150px;
        object-fit: contain;
        display: inline-block;
    }

    .logo-tpq {
        width: 150px;
        height: 150px;
        object-fit: contain;
        display: inline-block;
    }

    .org-text {
        text-align: center;
    }

    .arabic-line {
        margin-bottom: 5px;
        text-align: center;
    }

    .arabic-line img {
        width: 360px;
        height: auto;
        display: inline-block;
    }

    .org-label {
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 1px;
        line-height: 1.15;
    }

    .method-name {
        font-family: 'DejaVu Serif', serif;
        font-size: 31px;
        font-weight: bold;
        letter-spacing: 1px;
        line-height: 1.12;
    }

    .branch {
        font-size: 17px;
        font-weight: bold;
        letter-spacing: 1px;
        line-height: 1.1;
    }

    .letterhead-main {
        background: #f1d9bd;
        color: #000;
        padding: 14px 18px 16px;
        text-align: center;
    }

    .school-small {
        font-size: 25px;
        font-weight: 900;
        letter-spacing: 2px;
        line-height: 1.15;
    }

    .school-name {
        font-size: 45px;
        font-weight: 900;
        letter-spacing: 1px;
        line-height: 1.05;
        margin: 6px 0 10px;
    }

    .school-number {
        font-size: 24px;
        font-weight: 900;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .school-address {
        font-size: 16px;
        font-weight: bold;
        line-height: 1.25;
    }

    .report-title {
        text-align: center;
        color: #111827;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        margin-top: 8px;
    }

    .report-date {
        text-align: center;
        font-size: 10px;
        color: #4b5563;
        margin-top: 4px;
        margin-bottom: 12px;
    }

    .divider {
        border: none;
        border-top: 2px solid #642400;
        margin-bottom: 14px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .main-table {
        margin-bottom: 18px;
    }

    .main-table thead tr {
        background: #642400;
        color: #fff;
    }

    .main-table thead th {
        padding: 7px 6px;
        text-align: center;
        font-size: 9px;
        border: 0.5px solid #8b5a2b;
    }

    .main-table tbody td {
        padding: 6px;
        border: 0.5px solid #d1d5db;
        font-size: 9px;
        vertical-align: middle;
    }

    .main-table tbody tr:nth-child(even) {
        background: #f8f4ef;
    }

    .td-center {
        text-align: center;
    }

    .td-right {
        text-align: right;
    }

    .badge-spp {
        color: #166534;
        font-weight: bold;
    }

    .badge-pembangunan {
        color: #1d4ed8;
        font-weight: bold;
    }

    .badge-income {
        color: #166534;
        font-weight: bold;
    }

    .badge-expense {
        color: #dc2626;
        font-weight: bold;
    }

    .summary-section {
        width: 48%;
        margin-left: auto;
        margin-top: 8px;
    }

    .summary-table thead th {
        background: #642400;
        color: #fff;
        padding: 8px;
        font-size: 10px;
        border: 0.5px solid #8b5a2b;
    }

    .summary-table tbody td {
        padding: 8px 10px;
        font-size: 10px;
        border: 0.5px solid #d7c2a6;
    }

    .summary-table tbody tr {
        background: #fffaf4;
    }

    .summary-table .saldo-row td {
        background: #f1d9bd;
        font-weight: bold;
        font-size: 11px;
    }

    .val-total {
        color: #166534;
        text-align: right;
        font-weight: bold;
    }

    .val-expense {
        color: #dc2626;
        text-align: right;
        font-weight: bold;
    }

    .footer-line {
        border-top: 0.5px solid #d7c2a6;
        margin-top: 24px;
        padding-top: 10px;
    }

    .footer-text {
        text-align: center;
        font-size: 9px;
        color: #6b7280;
        font-style: italic;
    }

    .empty-row td {
        text-align: center;
        padding: 22px;
        color: #6b7280;
        font-style: italic;
    }
</style>
