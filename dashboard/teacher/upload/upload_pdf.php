<?php
require '../../../fpdf/fpdf.php'; // Path to PDF Parser
require '../../../tcpdf/tcpdf.php'; // Path to TCPDF dependency
require '../../../database/db.php'; // Database connection
require_once '../../../fpdf/fpdi/src/autoload.php'; // Path to FPDI autoload file


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $file = $_FILES['pdf_file'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];

        // Check if the file is a valid PDF
        $fileType = mime_content_type($fileTmp);
        if ($fileType != 'application/pdf') {
            die("Please upload a valid PDF file.");
        }

        // Extract text from PDF
        try {
            $pdf = new FPDI();
            $pageCount = $pdf->setSourceFile($fileTmp);
            $text = '';

            // Extract text from all pages
            for ($page = 1; $page <= $pageCount; $page++) {
                $tplIdx = $pdf->importPage($page);
                $text .= $pdf->getText($tplIdx); // This extracts the text
            }

            // Process text into chunks
            $chunkSize = 5000;
            $overlapSize = 25;
            $chunks = [];
            $length = strlen($text);
            $start = 0;

            while ($start < $length) {
                $chunk = substr($text, $start, $chunkSize);

                // Add overlap from the previous chunk
                if ($start > 0) {
                    $chunk = substr($text, $start - $overlapSize, $chunkSize + $overlapSize);
                }

                $chunks[] = $chunk;
                $start += $chunkSize;
            }

            // Store chunks in the database
            $chunkNumber = 1;
            foreach ($chunks as $chunk) {
                $stmt = $conn->prepare("INSERT INTO pdf_chunks (file_name, chunk_number, content) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $fileName, $chunkNumber, $chunk);
                $stmt->execute();
                $chunkNumber++;
            }

            echo "PDF uploaded and processed successfully!";
        } catch (Exception $e) {
            echo "Error processing PDF: " . $e->getMessage();
        }
    } else {
        echo "Error uploading file.";
    }
}
?>

