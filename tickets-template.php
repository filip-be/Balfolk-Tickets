<?php
/**
 * Template Name: Tickets Page
 *
 * @package accesspress_parallax
 */
// reference the Dompdf namespace
use Dompdf\Dompdf;

// Get <img> tag
function getImage($uri, $width, $height, $styles) {
	if(is_null($uri)) {
		return '';
	}
	$contextOptions = array(
		"ssl" => array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);
	$img_content = file_get_contents($uri, false, stream_context_create($contextOptions));
	$encodedImage = base64_encode($img_content);
	if(is_null($styles)) {
		$styles = '';
	}
	$styles .= "width: {$width}px; ";
	$styles .= "height: {$height}px; ";
	
	return '<img style="'.$styles.'" src="data:image/png;base64,'.$encodedImage.'"/>';
}

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
		
		$eventName = 'Ticket';
		$orderId = $order->OrderId;
		$ticketStr = pll__('BFTTicket');
		$simplifiedTicketName = get_bloginfo('name').' - '.$ticketStr;
		
		// Generate HTML for PDF
		ob_start();
		?>
		
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<style>
			body		{ font-family: 'DejaVu Sans'; font-size: 13px; }
			.ticket 	{ width: 100%; }
			.header 	{ text-align: center; width: 100%; clear: both; float: left; }
			.header h2 	{ margin: 0; }
			.qrCode img	{ float: right; }
			.qrCode p	{ display: block; width: 150px; text-align: center; float: right; clear: right; color: gray; font-size: 0.8em; }
			.product img { float: left; }
			.product h3 { margin: 7px 7px 0 0; width: 100%; }
			.footer {
				color: white;
				background-color: black;
				width: 100%;
				text-align: right;
				padding: 5px;
				position: fixed; 
				bottom: 0px;
			}
		</style>
		<title><?php echo $simplifiedTicketName; ?></title>
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
				if($eventName == 'Ticket')
				{
					$eventName = $eventDef->Name;
				}
				// Get product definition
				$wcProduct = wc_get_product(pll_get_post($ticketDef->ProductID));
				if(is_null($wcProduct))
				{
					return false;
				}
				
				// Product thumbnail 
				$thumbnailUri = false;
				$thumbnailUri = get_the_post_thumbnail_url($wcProduct->get_id());
				if($thumbnailUri == false) {
					$thumbnailUri = wc_placeholder_img_src();
				}
				$thumbnailImg = getImage($thumbnailUri, 65, 65, "vertical-align: top; margin: 0 5px 5px 0;", null);
				
				// QR code image
				$qrCodeImage = getImage('https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl='.$orderTicket->Hash.'&chld=Q|3', 150, 150, null);
				
				// Page div START
				print '<div class="ticket">';
				
				// Event name
				print "<div class=\"header\"><h2>{$eventDef->Name} - {$ticketStr} #{$orderTicket->ID}</h2></div><br/><br/>";
				
				// QR code
				print '<div class="qrCode">'.$qrCodeImage.'<p>'.$orderTicket->Hash.'</p></div>';
				
				// Left column
				print "<div class=\"product\">";
				print "<h3>{$wcProduct->get_name()}</h3>";
				print "$thumbnailImg";
				print '<p>'.nl2br($wcProduct->get_short_description()).'</p>';
				print "</div>";
				
				
				// Footer
				print '<div class="footer">© '.date("Y").' '.get_bloginfo('name').'</div>';
				
				// Page div END
				print '</div>';
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
		$ticketName = $eventName.' - '.$ticketStr.'.pdf';
		$dompdf->stream($ticketName, array("Attachment" => false));
		// print $html;
		
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