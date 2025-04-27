# File statuses
* `in_progress`: The file is created.
* `completed`: The file (and chunks) is fully uploaded and ready for use.
* `failed:checksum`: The file upload failed due to a checksum mismatch. This means the uploaded file does not match the expected checksum, indicating potential corruption or tampering, wrong chunks order.
* `failed:chunks-merge`: The file upload failed during the merging process. This could be due to various reasons, such as server errors or issues with the uploaded chunks.

# Upload statuses
* `in_progress`: The chunk is ready and waiting for upload.
* `completed`: The chunk has been successfully uploaded.
