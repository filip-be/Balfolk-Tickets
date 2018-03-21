<?php
/**
 * Template Name: Tickets Page
 *
 * @package accesspress_parallax
 */
// reference the Dompdf namespace
use Dompdf\Dompdf;

// Show tickets
function showTicket() {
	// Order hash must not be empty
	if(isset($_GET['id']))
	{
		// Find BFT order
		$order = BFT_Order::GetByKey($_GET['id']);
		if(is_null($order)) {
			return false;
		}
		
		// Generate HTML for PDF
		ob_start();
		?>
		
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<style>
			body{
				font-family: 'DejaVu Sans';
			}
		</style>
		<title>Ticket</title>
	</head>
	<body>
		<?php
			// Loop tickets
			for($ticketNum = 0; $ticketNum < count($order->Tickets); )
			{
				$orderTicket = $order->Tickets[$ticketNum];
				// Get ticket definition
				$ticketDef = BFT_Ticket::GetById($orderTicket->TicketID);
				if(is_null($ticketDef))
				{
					return false;
				}
				// Get event definition
				$eventDef = BFT_Event::GetById($ticketDef->EventID);
				if(is_null($eventDef))
				{
					return false;
				}
				// Get product definition
				$wcProduct = wc_get_product(pll_get_post($ticketDef->ProductID));
				if(is_null($wcProduct))
				{
					return false;
				}
				
				// Print QR code
				$qrCode = base64_encode(file_get_contents("https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl='.$orderTicket->Hash.'&chld=Q|3"));
				print '<p style="float: right; width: 150px; height: 150px;"><img src="data:image/png;base64,'.$qrCode.'"/></p>';
				
				// Paragraph
				print '<p style="float: left">';
				// Print event name, product name & short description
				print "<h2>{$eventDef->Name}</h2>";
				print "<h3>{$wcProduct->get_name()}</h3>";
				print "<p>{$wcProduct->get_short_description()}</p>";
				
				// Paragraph end
				print '</p>';
				
				// Page break
				if(++$ticketNum < count($order->Tickets))
				{
					print '<div style="page-break-after: always;"></div>';
				}
			}
		?>
	</body>
</html>
		<?php
		$html = ob_get_clean();
		
		
		require_once 'dompdf/autoload.inc.php';

		// instantiate and use the dompdf class
		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('A6', 'landscape');

		// Render the HTML as PDF
		$dompdf->render();

		// Output the generated PDF to Browser
		$dompdf->stream("ticket.pdf", array("Attachment" => false));
		//print $html;
		
		return true;
	}
	return false;
}

if(!showTicket())
{
	get_header();
	?>
	<section class="parallax-section clearfix default_template">
		<div class="mid-content">
			<h1><span>404</span></h1>
			<div class="parallax-content">
				<div class="page-content">
					<p>Bilet nie został znaleziony.</p>
					<p>Ticket was not found.</p>
				</div>
			</div>
		</div>
	</section>
	<?php
	get_footer();
}