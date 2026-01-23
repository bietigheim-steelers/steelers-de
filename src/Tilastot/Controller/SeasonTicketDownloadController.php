<?php
namespace App\Tilastot\Controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Tilastot\Model\SeasonTicket;
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
    $sheet->setCellValue('J1', 'Form');
    $sheet->setCellValue('K1', 'Mitglied');
    $sheet->setCellValue('L1', 'Bezahlung');
    $sheet->setCellValue('N1', 'Preis');
    $sheet->setCellValue('O1', 'Family&Friends');
    $sheet->setCellValue('P1', 'DK letzte Saison');
    $sheet->setCellValue('Q1', 'Bestelldatum');
    
    // Style header row
    $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
    
    // Fetch all season tickets from database
    $seasonTickets = SeasonTicket::findAll();
    
    // Get category map
    $categoryMap = SeasonTicketController::getCategoryMap();
    
    // Add data rows
    $row = 2;
    if ($seasonTickets !== null) {
      foreach ($seasonTickets as $ticket) {
        $sheet->setCellValue('A' . $row, strtoupper($ticket->ticket_type));
        $sheet->setCellValue('B' . $row, $ticket->seat_block ?: '');
        $sheet->setCellValue('C' . $row, $ticket->seat_row ?: '0');
        $sheet->setCellValue('D' . $row, $ticket->seat_seat ?: '0');
        $sheet->setCellValue('E' . $row, $ticket->customer_firstname);
        $sheet->setCellValue('F' . $row, $ticket->customer_name);
        $sheet->setCellValue('G' . $row, $categoryMap[$ticket->ticket_category] ?? $ticket->ticket_category);
        $sheet->setCellValue('H' . $row, $ticket->customer_email);
        $sheet->setCellValue('J' . $row, $ticket->ticket_form);
        $sheet->setCellValue('K' . $row, $ticket->customer_member ? 'Ja' : 'Nein');
        $sheet->setCellValue('L' . $row, $ticket->ticket_payment);
        $sheet->setCellValue('N' . $row, $ticket->price);
        $sheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode( 
          \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
        );
        $sheet->setCellValue('O' . $row, ''); // Family&Friends - to be determined
        $sheet->setCellValue('P' . $row, $ticket->customer_last_season ? 'Ja' : 'Nein');

        $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ticket->tstamp);
        $sheet->setCellValue('Q' . $row, $excelDateValue);
        $sheet->getStyle('Q' . $row)->getNumberFormat()->setFormatCode(
          \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
        );

        $row++;
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