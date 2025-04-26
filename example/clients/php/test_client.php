<?php

// filepath: /Users/michael/github.com/files-nest/backend/example/clients/php/upload_client.php

/**
 * Simple PHP client to upload a file in chunks to the files-nest API.
 *
 * Reads the API token from the API_TOKEN environment variable.
 *
 * Usage:
 * API_TOKEN=your_token php upload_client.php <file_path> [api_base_url] [chunk_size_mb]
 *
 * Example:
 * API_TOKEN=1|abc...xyz php upload_client.php ./my_large_file.zip http://localhost/api 5
 */

// --- Configuration ---
$apiToken = getenv('API_TOKEN');
$filePath = $argv[1] ?? null;
$apiBaseUrl = $argv[2] ?? 'http://localhost/api'; // Default API URL
$chunkSizeMb = (int) ($argv[3] ?? 5); // Default chunk size in MB
$chunkSizeBytes = $chunkSizeMb * 1024 * 1024;

// --- !!! ASSUMPTION !!! ---
// The OpenAPI spec doesn't define chunk uploading. We assume an endpoint like this exists.
// You MUST verify and adjust this based on the actual backend implementation.
// $chunkUploadEndpoint = $apiBaseUrl . '/upload-chunk'; // <-- OLD, incorrect endpoint
$createFileEndpoint = $apiBaseUrl.'/files?include=uploads';
$uploadChunkBaseEndpoint = $apiBaseUrl.'/files/'; // Base endpoint for uploads
// --- End Assumption ---

// --- Validation ---
if (empty($apiToken)) {
    echo "Error: API_TOKEN environment variable not set.\n";
    exit(1);
}
if (empty($filePath) || ! file_exists($filePath) || ! is_readable($filePath)) {
    echo "Error: Invalid or non-readable file path provided.\n";
    echo "Usage: API_TOKEN=your_token php upload_client.php <file_path> [api_base_url] [chunk_size_mb]\n";
    exit(1);
}
if ($chunkSizeMb <= 0) {
    echo "Error: Chunk size must be a positive number (MB).\n";
    exit(1);
}

// --- Helper Functions ---
function send_request(string $url, string $method = 'POST', ?array $jsonData = null, ?string $filePath = null, ?array $formData = null, bool $sendRawBody = false): array
{
    global $apiToken;

    $ch = curl_init();
    $headers = [
        'Accept: application/json',
        'Authorization: Bearer '.$apiToken,
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, false); // Handle errors manually
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // Set the custom request method (e.g., PUT, PATCH, POST)

    if ($jsonData !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
    } elseif ($sendRawBody && $filePath !== null && file_exists($filePath)) {
        // Send raw file content as request body
        $headers[] = 'Content-Type: application/octet-stream'; // Or appropriate content type
        $fileHandle = fopen($filePath, 'rb');
        if (! $fileHandle) {
            curl_close($ch);

            return ['success' => false, 'http_code' => 0, 'error' => "Curl error: Failed to open file for raw body upload: {$filePath}", 'body' => null];
        }
        // Use CURLOPT_READFUNCTION/INFILE for large files to avoid loading into memory
        // curl_setopt($ch, CURLOPT_PUT, true); // Removed: Rely on CURLOPT_CUSTOMREQUEST
        curl_setopt($ch, CURLOPT_UPLOAD, true); // Preferred way for PUT/PATCH uploads with INFILE
        curl_setopt($ch, CURLOPT_INFILE, $fileHandle);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
        // Note: CURLOPT_POSTFIELDS cannot be used with CURLOPT_UPLOAD/CURLOPT_INFILE
    } elseif ($filePath !== null && $formData !== null) {
        // Existing multipart/form-data logic (might not be needed anymore for chunks)
        $formData['chunk'] = new CURLFile($filePath, mime_content_type($filePath) ?: 'application/octet-stream', basename($filePath));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        // Don't set Content-Type header for multipart/form-data, curl handles it
    } elseif ($formData !== null) {
        // For non-file multipart data if needed
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Close the file handle if it was opened for raw upload
    if (isset($fileHandle) && is_resource($fileHandle)) {
        fclose($fileHandle);
    }

    if ($error) {
        return ['success' => false, 'http_code' => 0, 'error' => 'Curl error: '.$error, 'body' => null];
    }

    $responseData = json_decode($responseBody, true);

    // Allow successful PUT requests with potentially empty bodies (like chunk uploads)
    if ($httpCode >= 400) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => "API Error (HTTP {$httpCode}): ".($responseData['message'] ?? $responseBody), 'body' => $responseData];
    }

    return ['success' => true, 'http_code' => $httpCode, 'error' => null, 'body' => $responseData];
}

// --- Main Logic ---
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$totalChunks = ceil($fileSize / $chunkSizeBytes);
$fileChecksum = hash_file('sha256', $filePath);

echo "Starting upload for: {$fileName}\n";
echo 'File size: '.round($fileSize / 1024 / 1024, 2)." MB\n";
echo "Chunk size: {$chunkSizeMb} MB ({$chunkSizeBytes} bytes)\n";
echo "Total chunks: {$totalChunks}\n";
echo "Checksum (SHA256): {$fileChecksum}\n"; // Corrected label
echo "API Endpoint: {$apiBaseUrl}\n";
echo "------------------------------------\n";

// 1. Create File Record
echo "Step 1: Creating file record...\n";
$createFileData = [
    'name' => $fileName,
    'checksum' => $fileChecksum,
    'create_datetime' => '2020-01-01 00:00:00',
    'chunks_count' => $totalChunks, // Added chunks_count
    // Add any other required fields based on the actual API requirements
    // 'create_datetime' might be set server-side
];
$createResponse = send_request($createFileEndpoint, 'POST', $createFileData);

if (! $createResponse['success']) {
    echo 'Error creating file record: '.$createResponse['error']."\n";
    exit(1);
}

$fileId = $createResponse['body']['data']['id'] ?? null;
$uploadRecords = $createResponse['body']['data']['uploads'] ?? null; // Get upload records

if (! $fileId || ! $uploadRecords) {
    echo "Error: Could not get file ID or upload records from response.\n";
    print_r($createResponse['body']);
    exit(1);
}

if (count($uploadRecords) != $totalChunks) {
    echo "Error: Mismatch between expected chunks ({$totalChunks}) and upload records received (".count($uploadRecords).").\n";
    print_r($uploadRecords);
    exit(1);
}

echo "File record created successfully. File ID: {$fileId}\n";

// Sort upload records by number just in case they aren't ordered
usort($uploadRecords, fn ($a, $b) => ($a['number'] ?? 0) <=> ($b['number'] ?? 0));

// 2. Upload Chunks
echo "Step 2: Uploading chunks using PATCH...\n";
$fileHandle = fopen($filePath, 'rb');
if (! $fileHandle) {
    echo "Error opening file for reading.\n";
    exit(1);
}

$chunkNumber = 1;
$bytesUploaded = 0;
$tempChunkFile = sys_get_temp_dir().'/chunk_upload_'.uniqid(); // Temporary file for curl

foreach ($uploadRecords as $uploadRecord) {
    $uploadId = $uploadRecord['id'] ?? null;
    $expectedChunkNumber = $uploadRecord['number'] ?? null;

    if ($uploadId === null || $expectedChunkNumber === null) {
        echo 'Error: Invalid upload record found: '.print_r($uploadRecord, true)."\n";
        fclose($fileHandle);
        if (file_exists($tempChunkFile)) {
            unlink($tempChunkFile);
        }
        exit(1);
    }

    // Sanity check if the loop order matches the expected chunk number
    if ($expectedChunkNumber !== $chunkNumber) {
        echo "Error: Upload record number mismatch. Expected {$chunkNumber}, got {$expectedChunkNumber}. Ensure records are sorted.\n";
        fclose($fileHandle);
        if (file_exists($tempChunkFile)) {
            unlink($tempChunkFile);
        }
        exit(1);
    }

    echo "Uploading chunk {$chunkNumber}/{$totalChunks} (Upload ID: {$uploadId})... ";

    $chunkData = fread($fileHandle, $chunkSizeBytes);
    if ($chunkData === false && ! feof($fileHandle)) { // Allow false only if EOF
        echo "Error reading chunk {$chunkNumber}.\n";
        fclose($fileHandle);
        if (file_exists($tempChunkFile)) {
            unlink($tempChunkFile);
        }
        exit(1);
    }

    // Check if chunkData is empty but not EOF (shouldn't happen with correct chunk size/count)
    if (empty($chunkData) && ! feof($fileHandle)) {
        echo "Warning: Read empty chunk {$chunkNumber} before EOF.\n";
        // Decide how to handle: continue, error out, etc.
        // For now, we'll skip trying to upload an empty chunk if it's not the last one
        if ($chunkNumber < $totalChunks) {
            echo "Skipping empty chunk.\n";
            $chunkNumber++;

            continue;
        }
        // If it's the last chunk and it's empty, that's okay.
    }
    // If we read an empty chunk at the very end, stop processing
    if (empty($chunkData) && feof($fileHandle) && $chunkNumber <= $totalChunks) {
        echo "Reached EOF with empty data for chunk {$chunkNumber}. Assuming upload complete.\n";
        break;
    }

    // Write chunk data to a temporary file for raw upload
    if (file_put_contents($tempChunkFile, $chunkData) === false) {
        echo "Error writing chunk {$chunkNumber} to temporary file.\n";
        fclose($fileHandle);
        if (file_exists($tempChunkFile)) {
            unlink($tempChunkFile);
        }
        exit(1);
    }

    // Construct the PATCH URL for the specific upload record
    $chunkUrl = sprintf('%s%s/uploads/%s', $uploadChunkBaseEndpoint, $fileId, $uploadId);

    // Send the chunk data as raw body using PATCH
    $chunkResponse = send_request($chunkUrl, 'PATCH', null, $tempChunkFile, null, true);

    if (! $chunkResponse['success']) {
        echo "Error uploading chunk {$chunkNumber} (Upload ID: {$uploadId}): ".$chunkResponse['error']."\n";
        fclose($fileHandle);
        if (file_exists($tempChunkFile)) {
            unlink($tempChunkFile);
        } // Clean up temp file
        exit(1);
    }

    $bytesUploaded += strlen($chunkData);
    $progress = $fileSize > 0 ? round(($bytesUploaded / $fileSize) * 100) : 100;
    echo "OK (HTTP {$chunkResponse['http_code']}). Progress: {$progress}%\n";

    $chunkNumber++;

    // Break if we've uploaded all expected chunks, even if file handle isn't EOF (e.g., extra null bytes)
    if ($chunkNumber > $totalChunks) {
        break;
    }
}

fclose($fileHandle);
if (file_exists($tempChunkFile)) {
    unlink($tempChunkFile);
} // Clean up temp file

// Check if we uploaded the expected number of chunks
$processedChunks = $chunkNumber - 1;
if ($processedChunks !== $totalChunks && $fileSize > 0) { // Check only if file is not empty
    // Use the pre-calculated variable in the echo statement
    echo "Warning: Expected to upload {$totalChunks} chunks, but processed {$processedChunks}. Check file size and chunk logic.\n";
    // Potentially exit with error depending on requirements
}

echo "------------------------------------\n";
echo "All chunks processed successfully.\n"; // Changed message slightly

// Remove the optional finalize step as merging is triggered by the backend

echo "Upload process complete for file ID: {$fileId}\n";
exit(0);
