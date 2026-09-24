<?php
$file = "H:/Downloads/Backup 09- 10/Store_Purchase&Sell/user/print_bill.php";
$content = file_get_contents($file);

$start = strpos($content, "<style>");
$end = strpos($content, "</style>") + 8;

$new_style = "<style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f7f7f7;
            color: #000;
        }
        .receipt-container {
            max-width: 380px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .shop-name {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: lowercase;
        }
        .shop-address {
            font-size: 14px;
            color: #333;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        .shop-phone {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .total-box {
            border: 2px solid #000;
            border-radius: 8px;
            padding: 15px 10px;
            text-align: center;
            margin-bottom: 25px;
        }
        .total-box-label {
            font-size: 13px;
            font-weight: bold;
        }
        .total-box-amount {
            font-size: 32px;
            font-weight: 900;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 10px 0;
            border-bottom: 1px dashed #ccc;
        }
        th {
            text-align: left;
            font-size: 14px;
            border-bottom: 2px solid #000;
        }
        .item-name {
            font-size: 15px;
            font-weight: bold;
            display: block;
        }
        .item-meta {
            font-size: 13px;
            color: #555;
            display: block;
            margin-top: 3px;
        }
        .item-price {
            font-size: 15px;
            font-weight: bold;
            vertical-align: top;
            white-space: nowrap;
        }
        .totals-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .totals-table td {
            border: none;
            padding: 6px 0;
            font-size: 14px;
        }
        .totals-table .totals-label {
            text-align: right;
            padding-right: 20px;
        }
        .totals-table .totals-value {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }
        .grand-total {
            font-size: 18px !important;
            padding-bottom: 15px !important;
        }
        .grand-total-border {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }
        .footer-info {
            text-align: center;
            font-size: 13px;
            color: #444;
            line-height: 1.6;
        }
        
        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .receipt-container {
                max-width: 100%;
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 10px;
            }
            /* Hide print urls and pagination */
            @page { margin: 0; }
        }
    </style>";

$content = substr($content, 0, $start) . $new_style . substr($content, $end);
file_put_contents($file, $content);
echo "Fixed style block";

