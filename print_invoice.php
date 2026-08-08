<?php
require_once 'config.php';
require_once './fpdf/fpdf.php';

$saleId = (int)($_GET['id'] ?? $_SESSION['last_sale_id'] ?? 0);
if (!$saleId) die('Invalid ID. <a href="invoice.html">← Return</a>');

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->bind_param('i', $saleId);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
if (!$sale) die('Record not found.');

$stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$stmt->bind_param('i', $saleId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  RxBill PDF Class — distinct from original
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
class RxBillPDF extends FPDF {
    var $invNo;
    var $invDate;

    // ── Header: two-tone bar instead of solid block ──
    function Header() {
        // Dark navy left strip
        $this->SetFillColor(15, 23, 41);
        $this->Rect(0, 0, 70, 28, 'F');

        // Teal right strip
        $this->SetFillColor(14, 165, 160);
        $this->Rect(70, 0, 140, 28, 'F');

        // Brand name on dark strip
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(8, 5);
        $this->Cell(55, 8, 'RxBill', 0, 1, 'L');

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(45, 212, 191);   // teal-light
        $this->SetXY(8, 13);
        $this->Cell(55, 5, 'Pharmacy Suite', 0, 1, 'L');

        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(200, 220, 220);
        $this->SetXY(8, 19);
        $this->Cell(55, 4, 'rxbill.health', 0, 0, 'L');

        // Invoice label on teal strip
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(72, 4);
        $this->Cell(136, 7, 'SALES INVOICE', 0, 1, 'R');

        $this->SetFont('Arial', '', 8);
        $this->SetXY(72, 12);
        $this->Cell(136, 5, 'Invoice: ' . $this->invNo, 0, 1, 'R');

        $this->SetXY(72, 18);
        $this->Cell(136, 5, 'Date: ' . $this->invDate, 0, 1, 'R');

        $this->SetTextColor(0, 0, 0);
        $this->SetY(32);
    }

    // ── Footer: minimal bottom bar ──
    function Footer() {
        $this->SetY(-13);
        $this->SetDrawColor(14, 165, 160);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY() - 2, 200, $this->GetY() - 2);

        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(107, 122, 153);
        $this->Cell(95, 5, 'RxBill Pharmacy Suite  |  Computer-generated invoice', 0, 0, 'L');
        $this->Cell(95, 5, 'Page ' . $this->PageNo() . '  |  ' . date('d M Y, h:i A'), 0, 0, 'R');
    }

    // ── Section label: left-bordered pill ──
    function SectionLabel($text, $r, $g, $b) {
        $this->SetFillColor($r, $g, $b);
        $this->Rect(10, $this->GetY(), 3, 6, 'F');

        $this->SetFillColor(240, 248, 255);
        $this->SetDrawColor(220, 228, 240);
        $this->RoundedRect(13, $this->GetY(), 185, 6, 2, 'DF');

        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor($r, $g, $b);
        $this->SetX(16);
        $this->Cell(180, 6, strtoupper($text), 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }

    // ── RoundedRect helper ──
    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k));
        $xc = $x+$w-$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k));
        $this->_Arc($xc+$r*$MyArc, $yc-$r, $xc+$r, $yc-$r*$MyArc, $xc+$r, $yc);
        $xc = $x+$w-$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc+$r, $yc+$r*$MyArc, $xc+$r*$MyArc, $yc+$r, $xc, $yc+$r);
        $xc = $x+$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc-$r*$MyArc, $yc+$r, $xc-$r, $yc+$r*$MyArc, $xc-$r, $yc);
        $xc = $x+$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k));
        $this->_Arc($xc-$r, $yc-$r*$MyArc, $xc-$r*$MyArc, $yc-$r, $xc, $yc-$r);
        $this->_out($op);
    }

    function _Arc($x1,$y1,$x2,$y2,$x3,$y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ',
            $x1*$this->k,($h-$y1)*$this->k,
            $x2*$this->k,($h-$y2)*$this->k,
            $x3*$this->k,($h-$y3)*$this->k));
    }

    // ── Info pair (label + value side by side) ──
    function InfoPair($label, $value, $w1 = 28, $w2 = 70) {
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(107, 122, 153);
        $this->Cell($w1, 5, $label, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(15, 23, 41);
        $this->Cell($w2, 5, $value ?: '—', 0, 0);
    }

    // ── Table header ──
    function TblHead($cols, $widths) {
        $this->SetFillColor(15, 23, 41);
        $this->SetTextColor(230, 240, 255);
        $this->SetFont('Arial', 'B', 7.5);
        foreach ($cols as $i => $c) {
            $align = in_array($i, [0,1,2]) ? 'L' : 'C';
            if ($i === count($cols) - 1) $align = 'R';
            $this->Cell($widths[$i], 7, $c, 0, 0, $align, true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  Build PDF
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$pdf = new RxBillPDF('P', 'mm', 'A4');
$pdf->invNo   = $sale['invoice_no'];
$pdf->invDate = date('d M Y', strtotime($sale['invoice_date']));
$pdf->SetMargins(10, 36, 10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

// ━━ Patient ━━
$pdf->SectionLabel('Patient Details', 99, 102, 241);

$pdf->InfoPair('Name:', $sale['patient_name']);
$pdf->InfoPair('Phone:', $sale['patient_phone']);
$pdf->Ln();

$pdf->InfoPair('Address:', $sale['patient_address'], 28, 165);
$pdf->Ln(7);

// ━━ Doctor ━━
$pdf->SectionLabel('Referring Doctor', 14, 165, 160);

$pdf->InfoPair('Doctor:', $sale['doctor_name']);
$pdf->InfoPair('Reminder Set:', $sale['reminder'] ? 'Yes' : 'No');
$pdf->Ln(8);

// ━━ Products ━━
$pdf->SectionLabel('Product Details', 5, 150, 105);

$cols   = ['#', 'Product Name', 'Salt / Composition', 'Batch', 'Exp', 'Qty', 'Rate', 'GST%', 'MRP', 'Disc%', 'Total'];
$widths = [7, 36, 30, 14, 12, 7, 15, 11, 15, 11, 15];
$pdf->TblHead($cols, $widths);

$n = 1; $shade = false;
foreach ($items as $item) {
    $shade = !$shade;
    if ($shade) $pdf->SetFillColor(245, 252, 250);
    else        $pdf->SetFillColor(255, 255, 255);

    $pdf->SetFont('Arial', '', 7.2);
    $pdf->SetDrawColor(230, 235, 245);

    $pdf->Cell($widths[0], 6, $n++,                                                    'B', 0, 'C', $shade);
    $pdf->Cell($widths[1], 6, mb_strimwidth($item['product_name'], 0, 24),             'B', 0, 'L', $shade);
    $pdf->Cell($widths[2], 6, mb_strimwidth($item['salt'] ?? '', 0, 20),               'B', 0, 'L', $shade);
    $pdf->Cell($widths[3], 6, $item['batch_no'],                                       'B', 0, 'C', $shade);
    $pdf->Cell($widths[4], 6, $item['expiry_date'],                                    'B', 0, 'C', $shade);
    $pdf->Cell($widths[5], 6, $item['qty'],                                            'B', 0, 'C', $shade);
    $pdf->Cell($widths[6], 6, number_format($item['rate'], 2),                         'B', 0, 'R', $shade);
    $pdf->Cell($widths[7], 6, $item['gst_percent'].'%',                                'B', 0, 'C', $shade);
    $pdf->Cell($widths[8], 6, number_format($item['mrp'], 2),                          'B', 0, 'R', $shade);
    $pdf->Cell($widths[9], 6, $item['disc_percent'].'%',                               'B', 0, 'C', $shade);
    $pdf->Cell($widths[10],6, number_format($item['total'], 2),                        'B', 1, 'R', $shade);
}
$pdf->Ln(5);

// ━━ Summary block (right-aligned) ━━
$pdf->SectionLabel('Charges & Summary', 100, 116, 139);

// Additional charges — left column
$sumStartY = $pdf->GetY();
$lx = 10;

$pdf->SetXY($lx, $sumStartY);
$pdf->SetFillColor(245, 246, 250);
$pdf->SetDrawColor(210, 218, 230);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->Cell(50, 6, 'Charge Type', 'B', 0, 'L', true);
$pdf->Cell(28, 6, 'Amount (Rs.)', 'B', 1, 'R', true);

$charges = [
    'Lab Charge'       => $sale['lab_charge'],
    'Doctor Fee'       => $sale['doctor_charge'],
    'Injection Charge' => $sale['injection_charge'],
    'Nursing Charge'   => $sale['nursing_charge'],
];
$pdf->SetFont('Arial', '', 7.5);
foreach ($charges as $lbl => $amt) {
    $pdf->SetXY($lx, $pdf->GetY());
    $pdf->Cell(50, 5.5, $lbl, 'B', 0, 'L');
    $pdf->Cell(28, 5.5, number_format($amt, 2), 'B', 1, 'R');
}

// Summary — right column
$rx = 122;
$pdf->SetXY($rx, $sumStartY);

$summaryRows = [
    ['Total Discount',    number_format($sale['total_discount'], 2),    [220,38,38],  false],
    ['Product Subtotal',  number_format($sale['product_subtotal'], 2),  [15,23,41],   false],
    ['Additional Charges',number_format($sale['additional_charges'], 2),[15,23,41],   false],
    ['Rounding Off',      number_format($sale['rounding_off'], 2),      [107,122,153],false],
];

$pdf->SetDrawColor(210, 218, 230);
foreach ($summaryRows as $row) {
    [$lbl, $val, $rgb, $bold] = $row;
    $pdf->SetXY($rx, $pdf->GetY());
    $pdf->SetFont('Arial', $bold ? 'B' : '', 7.5);
    $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->Cell(58, 5.5, $lbl . ':', 'B', 0, 'L');
    $pdf->Cell(28, 5.5, $val,        'B', 1, 'R');
}

// Grand total row
$pdf->SetXY($rx, $pdf->GetY());
$pdf->SetFillColor(14, 165, 160);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(58, 9, 'GRAND TOTAL', 0, 0, 'L', true);
$pdf->Cell(28, 9, 'Rs. ' . number_format($sale['grand_total'], 2), 0, 1, 'R', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(10);

// ━━ Signatures ━━
$pdf->SetDrawColor(180, 190, 210);
$pdf->SetFont('Arial', '', 7.5);

$pdf->Cell(58, 14, '', 1, 0, 'C');
$pdf->Cell(8,  14, '', 0, 0);
$pdf->Cell(58, 14, '', 1, 0, 'C');
$pdf->Cell(8,  14, '', 0, 0);
$pdf->Cell(50, 14, '', 1, 1, 'C');

$pdf->SetTextColor(107, 122, 153);
$pdf->Cell(58, 5, 'Patient / Receiver', 0, 0, 'C');
$pdf->Cell(8,  5, '', 0, 0);
$pdf->Cell(58, 5, 'Consulting Doctor', 0, 0, 'C');
$pdf->Cell(8,  5, '', 0, 0);
$pdf->Cell(50, 5, 'Authorised Signatory', 0, 1, 'C');

// ━━ Terms ━━
$pdf->Ln(6);
$pdf->SetFont('Arial', 'I', 6.5);
$pdf->SetTextColor(160, 174, 192);
$pdf->MultiCell(0, 3.5,
    'Terms: Goods sold are non-returnable. This is a computer-generated bill and is valid without a physical signature. ' .
    'All disputes are subject to local jurisdiction. For queries, contact your pharmacist.',
    0, 'C');

$pdf->Output('I', 'RxBill_' . $sale['invoice_no'] . '.pdf');
?>
