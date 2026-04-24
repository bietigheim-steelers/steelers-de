<?php

namespace App\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\SeasonTicket;
use Contao\CoreBundle\Framework\ContaoFramework;

/**
 * @Route(defaults={"_scope" = "backend"})
 * @internal
 */
class SeasonTicketDownloadController {

  private $framework;

  public function __construct(ContaoFramework $framework) {
    $this->framework = $framework;
  }

  /**
   * @Route("/contao/seasontickets/download", name="backend_season_ticket_download")
   */
  public function triggerDownload(): StreamedResponse {
    $this->framework->initialize();
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add header row
    $sheet->setCellValue('A1', 'Typ');
    $sheet->setCellValue('B1', 'Block');
    $sheet->setCellValue('C1', 'Reihe');
    $sheet->setCellValue('D1', 'Platz');
    $sheet->setCellValue('E1', 'Vorname');
    $sheet->setCellValue('F1', 'Nachname');
    $sheet->setCellValue('G1', 'Kategorie');
    $sheet->setCellValue('H1', 'E-Mail');
    $sheet->setCellValue('I1', 'Form');
    $sheet->setCellValue('J1', 'Mitglied');
    $sheet->setCellValue('K1', 'Bezahlung');
    $sheet->setCellValue('L1', 'Preis');
    $sheet->setCellValue('M1', 'DK letzte Saison');
    $sheet->setCellValue('N1', 'Bestelldatum');
    $sheet->setCellValue('O1', 'Bestellnummer');
    $sheet->setCellValue('P1', 'Eventim Email');
    $sheet->setCellValue('Q1', 'Eventim Kundennummer');
    
    // Style header row
    $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
    
    // Fetch all season tickets from database
    $seasonTickets = SeasonTicket::findAll();
    
    // Get category map
    $categoryMap = SeasonTicketController::getCategoryMap();
    
    $familySeatCount = [
      'familie1' => 3,
      'familie2' => 3,
      'familie3' => 4,
    ];

    // Add data rows
    $row = 2;
    if ($seasonTickets !== null) {
      foreach ($seasonTickets as $ticket) {
        $seatCount = $familySeatCount[$ticket->ticket_category] ?? 1;

        for ($seatOffset = 0; $seatOffset < $seatCount; $seatOffset++) {
          $sheet->setCellValue('A' . $row, strtoupper($ticket->ticket_type));
          $sheet->setCellValue('B' . $row, $ticket->seat_block ?: '');
          $sheet->setCellValue('C' . $row, $ticket->seat_row ?: '0');
          $sheet->setCellValue('D' . $row, $seatOffset === 0 ? ($ticket->seat_seat ?: '0') : ((int) $ticket->seat_seat + $seatOffset));
          $sheet->setCellValue('E' . $row, $ticket->customer_firstname);
          $sheet->setCellValue('F' . $row, $ticket->customer_name);
          $sheet->setCellValue('G' . $row, $categoryMap[$ticket->ticket_category] ?? $ticket->ticket_category);
          $sheet->setCellValue('H' . $row, $ticket->customer_email);
          $sheet->setCellValue('I' . $row, $ticket->ticket_form);
          $sheet->setCellValue('J' . $row, $ticket->customer_member ? 'Ja' : 'Nein');
          $sheet->setCellValue('K' . $row, $ticket->ticket_payment);
          $sheet->setCellValue('L' . $row, $seatOffset === 0 ? $ticket->price : 0);
          $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode(
            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
          );
          $sheet->setCellValue('M' . $row, $ticket->customer_last_season);

          $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ticket->tstamp);
          $sheet->setCellValue('N' . $row, $excelDateValue);
          $sheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode(
            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
          );

          $sheet->setCellValue('O' . $row, $ticket->order_number);
          $sheet->setCellValue('P' . $row, $ticket->eventim_email);
          $sheet->setCellValue('Q' . $row, $ticket->eventim_account);

          $row++;
        }
      }
    }
    
    // Auto-size columns
    foreach (range('A', 'Q') as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Create the response with file download
    $response = new StreamedResponse(function() use ($spreadsheet) {
      $writer = new Xlsx($spreadsheet);
      $writer->save('php://output');
    });
    
    // Set headers to force download
    $filename = 'season_tickets_' . date('Y-m-d_His') . '.xlsx';
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->set('Cache-Control', 'max-age=0');
    
    return $response;
  }
}