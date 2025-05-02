# Uploading a File in Chunks

The process involves three main steps: 
first, retrieving the server settings to determine the maximum upload size, 
second, informing the server about the new file and the number of chunks it will be split into, 
and third, uploading each chunk individually.

## Step 1: Retrieve Server Settings

Make a GET request to the `/api/settings` endpoint to retrieve the maximum upload size allowed by the server.

Set the following headers:

```http
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

Response: The server will respond with a JSON object containing the `upload_max_size` value (in bytes). 
Use this value to calculate the number of chunks required for your file.

### Example Calculation

Divide the total file size (in bytes) by the `upload_max_size` value to determine the number of chunks. Round up to the nearest whole number.

```text
chunks_count = ceil(file_size / upload_max_size)
```

## Step 2: Initiate the File Upload

Make a POST request to the `/api/files` endpoint.

Set the following headers:

```http
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json 
```

Provide a JSON body containing:

```text
name: The desired filename (e.g., "my_document.pdf").
create_datetime: The original creation timestamp of the file (e.g., "2024-01-15 10:30:00").
checksum: The checksum (e.g., SHA256) calculated for the entire file. This will be used by the server to verify the integrity of the final merged file. (e.g., "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7").
chunks_count: The total number of chunks calculated in Step 1.
```

Response: The server will respond with HTTP status 201 Created. 
The response body will contain the details of the newly created file record, including its id (let's call this `file_id`). 
Crucially, it will also include an `uploads` array. 
Each object in this array represents a chunk placeholder, having its own `id` (let's call this `upload_id`) 
and `number` (from 1 to `chunks_count`).

## Step 3: Upload Each Chunk

Iterate through the chunks of your local file (from 1 to `chunks_count`).

For each chunk, find the corresponding upload object in the response from Step 2 based on the chunk number. Get its `upload_id`.

Make a PATCH request to `/api/files/{file_id}/uploads/{upload_id}`.

Replace `{file_id}` with the file ID obtained in Step 2.
Replace `{upload_id}` with the ID for the specific chunk you are uploading. 
(Note: `upload_id` is not the same as the chunk number;`number` shows the order in which a file will be assembled later).
Set the following headers:

```http
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/octet-stream (This is important! You are sending raw binary data).
```

The request body should be the raw binary data of the current chunk only. Do not wrap it in JSON.

Response: The server will respond with HTTP status 200 OK. 
The response body will show the updated status for that specific chunk, likely changing `status_name` to "completed".

Repeat this process for all chunks.

### Step 4: Completion

Once all chunks have been successfully uploaded (i.e., you've received a 200 OK for each PATCH request), the server-side process will automatically trigger:

- Assemble the chunks in the correct order.
- Verify the checksum of the assembled file against the checksum provided in Step 2.
- Update the overall file status (e.g., to "completed" or "failed:reason").

You can monitor the overall file status by periodically making a GET request to `/api/files/{file_id}`.
