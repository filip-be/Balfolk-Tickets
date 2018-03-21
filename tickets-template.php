<?php
/**
 * Template Name: Tickets Page
 *
 * @package accesspress_parallax
 */
// reference the Dompdf namespace
use Dompdf\Dompdf;
	
if(isset($_GET['id']))
{
	require_once 'dompdf/autoload.inc.php';
	

	// instantiate and use the dompdf class
	$dompdf = new Dompdf();
	$dompdf->loadHtml('hello world');

	// (Optional) Setup the paper size and orientation
	$dompdf->setPaper('A6', 'landscape');

	// Render the HTML as PDF
	$dompdf->render();

	// Output the generated PDF to Browser
	$dompdf->stream("ticket.pdf", array("Attachment" => false));
}
else
{
	get_header();
	
	print 'Ticket not found';
	
	get_footer();
}