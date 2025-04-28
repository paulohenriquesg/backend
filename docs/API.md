# API

## Authorization
* Authorization is done using a Bearer token. The token is passed in the `Authorization` header of the request.

## Files

### GET `/api/files`

#### Description
Get a list of files. The response has data about the files, such as their IDs, names, creation dates, checksums, and status.
You can include additional information about the files by using the [`include` parameter](#includes).

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Response
```json
{
    "data": [
        {
            "id": 13,
            "name": "amazon_food_reviews.zip",
            "create_datetime": "2020-01-01 00:00:00",
            "checksum": "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7",
            "created_at": "2025-04-26T14:53:22.000000Z",
            "updated_at": "2025-04-26T14:53:35.000000Z",
            "status_name": "completed"
        },
        {
            "id": 19,
            "name": "name3.jpg",
            "create_datetime": "2020-12-01 15:00:00",
            "checksum": "123",
            "created_at": "2025-04-26T20:15:15.000000Z",
            "updated_at": "2025-04-26T20:15:15.000000Z",
            "status_name": "in_progress"
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/127.0.0.1:8000\/api\/files",
        "per_page": 15,
        "to": 6,
        "total": 6
    }
}
```

### GET `/api/files/{file_id}`

#### Description
Get a specific file by its ID. The response includes data about the file, such as its ID, name, creation date, checksum, and status.
You can include additional information about the files by using the [`include` parameter](#includes).

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Response
```json
{
    "data": {
        "id": 13,
        "name": "amazon_food_reviews.zip",
        "create_datetime": "2020-01-01 00:00:00",
        "checksum": "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7",
        "created_at": "2025-04-26T14:53:22.000000Z",
        "updated_at": "2025-04-26T14:53:35.000000Z",
        "status_name": "completed"
    }
}
```

### POST `/api/files/search`

#### Description
Search for files based on filters. The response includes data about the files that match the search term, such as their IDs, names, creation dates, checksums, and status.
Filterable by `name`, `create_datetime`, `checksum`, `created_at`, and `updated_at`. 
The filters are applied using the following operators: `<`, `<=`, `>`, `>=`, `=`, `!=`, `like`, `not like`, `ilike`, `not ilike`, `in`, `not in`, `all in`, `any in`.
You can include additional information about the files by using the [`include` parameter](#includes).

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Request Body
```json
{
    "filters": [
        {
            "field": "name",
            "operator": "=",
            "value": "amazon_food_reviews.zip"
        }
    ]
}
```

#### Response
```json
{
    "data": [
        {
            "id": 13,
            "name": "amazon_food_reviews.zip",
            "create_datetime": "2020-01-01 00:00:00",
            "checksum": "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7",
            "created_at": "2025-04-26T14:53:22.000000Z",
            "updated_at": "2025-04-26T14:53:35.000000Z",
            "status_name": "completed"
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/127.0.0.1:8000\/api\/files",
        "per_page": 15,
        "to": 6,
        "total": 6
    }
}
```

### POST `/api/files`

#### Description
Create a new file. The request body should include the file name, creation date, checksum. The response includes data about the created file, such as its ID, name, creation date, checksum, and status. And the response also includes a list of uploads associated with the file, including their IDs, numbers, statuses, and timestamps.
File name is unique. It's impossible to recreate a file with the same name as an existing one (despite a status).
If you want to restart an upload process, you need to remove the file first.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Request
```json
{
    "name": "name6.jpg",
    "create_datetime": "2020-12-01 15:00:00",
    "checksum": "123",
    "chunks_count": 5
}
```

### Response (HTTP code 201)
```json
{
    "data": {
        "id": 26,
        "name": "name6.jpg",
        "create_datetime": "2020-12-01 15:00:00",
        "checksum": "123",
        "created_at": "2025-04-27T15:00:15.000000Z",
        "updated_at": "2025-04-27T15:00:15.000000Z",
        "status_name": "in_progress",
        "uploads": [
            {
                "id": 234,
                "number": 1,
                "created_at": "2025-04-27T15:00:15.000000Z",
                "updated_at": "2025-04-27T15:00:15.000000Z",
                "status_name": "in_progress"
            },
            {
                "id": 235,
                "number": 2,
                "created_at": "2025-04-27T15:00:15.000000Z",
                "updated_at": "2025-04-27T15:00:15.000000Z",
                "status_name": "in_progress"
            },
            {
                "id": 236,
                "number": 3,
                "created_at": "2025-04-27T15:00:15.000000Z",
                "updated_at": "2025-04-27T15:00:15.000000Z",
                "status_name": "in_progress"
            },
            {
                "id": 237,
                "number": 4,
                "created_at": "2025-04-27T15:00:15.000000Z",
                "updated_at": "2025-04-27T15:00:15.000000Z",
                "status_name": "in_progress"
            },
            {
                "id": 238,
                "number": 5,
                "created_at": "2025-04-27T15:00:15.000000Z",
                "updated_at": "2025-04-27T15:00:15.000000Z",
                "status_name": "in_progress"
            }
        ]
    }
}
```

### DELETE `/api/files/{file_id}`

#### Description
Delete a specific file by its ID. The response includes an old data about the deleted file, such as its ID, name, creation date, checksum, and status.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Response (HTTP code 200)
```json
{
    "data": {
        "id": 13,
        "name": "amazon_food_reviews.zip",
        "create_datetime": "2020-01-01 00:00:00",
        "checksum": "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7",
        "created_at": "2025-04-26T14:53:22.000000Z",
        "updated_at": "2025-04-26T14:53:35.000000Z",
        "status_name": "completed"
    }
}
```






## Uploads

### GET `/api/files/{file_id}/uploads`

#### Description
Get a list of uploads associated with a specific file. The response includes data about the uploads, such as their IDs, numbers, statuses, and timestamps.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Response
```json
{
    "data": [
        {
            "id": 199,
            "number": 1,
            "created_at": "2025-04-26T20:15:15.000000Z",
            "updated_at": "2025-04-26T20:15:15.000000Z",
            "status_name": "in_progress"
        },
        {
            "id": 200,
            "number": 2,
            "created_at": "2025-04-26T20:15:15.000000Z",
            "updated_at": "2025-04-26T20:15:15.000000Z",
            "status_name": "in_progress"
        },
        {
            "id": 201,
            "number": 3,
            "created_at": "2025-04-26T20:15:15.000000Z",
            "updated_at": "2025-04-26T20:15:15.000000Z",
            "status_name": "in_progress"
        },
        {
            "id": 202,
            "number": 4,
            "created_at": "2025-04-26T20:15:15.000000Z",
            "updated_at": "2025-04-26T20:15:15.000000Z",
            "status_name": "in_progress"
        },
        {
            "id": 203,
            "number": 5,
            "created_at": "2025-04-26T20:15:15.000000Z",
            "updated_at": "2025-04-26T20:15:15.000000Z",
            "status_name": "in_progress"
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http:\/\/127.0.0.1:8000\/api\/files",
        "per_page": 15,
        "to": 6,
        "total": 6
    }
}
```

### GET `/api/files/{file_id}/uploads/{upload_id}`

#### Description
Get a specific upload by its ID. The response includes data about the upload, such as its ID, file ID, number, status, and timestamps.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

#### Response
```json
{
    "data": {
        "id": 27,
        "number": 1,
        "status_name": "completed",
        "created_at": "2025-04-26T20:46:14.000000Z",
        "updated_at": "2025-04-26T20:46:14.000000Z"
    }
}
```

### PATCH `/api/files/{file_id}/uploads`

#### Description
Upload a binary data chunk to a specific upload. The request body should include binary data only.

#### Headers
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/octet-stream
```

#### Request
binary data

#### Response (HTTP code 200)
```json
{
    "data": {
        "id": 27,
        "number": 1,
        "status_name": "completed",
        "created_at": "2025-04-26T20:46:14.000000Z",
        "updated_at": "2025-04-26T20:46:14.000000Z"
    }
}
```


## Includes
### Parameter `?include=uploads`
#### Description
Include the uploads associated with the files in the response. The uploads contain information about the upload status, order and other details.

#### Response
```json
{
    "data": [
        {
            "id": 13,
            "name": "amazon_food_reviews.zip",
            "create_datetime": "2020-01-01 00:00:00",
            "checksum": "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7",
            "created_at": "2025-04-26T14:53:22.000000Z",
            "updated_at": "2025-04-26T14:53:35.000000Z",
            "status_name": "in_progress",
            "uploads": [
                {
                    "id": 27,
                    "number": 1,
                    "status_name": "completed",
                    "created_at": "2025-04-26T20:46:14.000000Z",
                    "updated_at": "2025-04-26T20:46:14.000000Z"
                },
                {
                    "id": 28,
                    "number": 2,
                    "status_name": "completed",
                    "created_at": "2025-04-26T20:46:14.000000Z",
                    "updated_at": "2025-04-26T20:46:14.000000Z"
                },
                {
                    "id": 29,
                    "number": 3,
                    "status_name": "completed",
                    "created_at": "2025-04-26T20:46:14.000000Z",
                    "updated_at": "2025-04-26T20:46:14.000000Z"
                }
            ]
        }
    ]
}
```
or

```json
{
    "data": {
        "id": 13,
        "name": "amazon_food_reviews.zip",
        "create_datetime": "2020-01-01 00:00:00",
        "checksum": "35617deab30259912a1a4f46b915d9ffd3fb4e6fd657bd573635edb9d4e317a7",
        "created_at": "2025-04-26T14:53:22.000000Z",
        "updated_at": "2025-04-26T14:53:35.000000Z",
        "status_name": "in_progress",
        "uploads": [
            {
                "id": 27,
                "number": 1,
                "status_name": "completed",
                "created_at": "2025-04-26T20:46:14.000000Z",
                "updated_at": "2025-04-26T20:46:14.000000Z"
            },
            {
                "id": 28,
                "number": 2,
                "status_name": "completed",
                "created_at": "2025-04-26T20:46:14.000000Z",
                "updated_at": "2025-04-26T20:46:14.000000Z"
            },
            {
                "id": 29,
                "number": 3,
                "status_name": "completed",
                "created_at": "2025-04-26T20:46:14.000000Z",
                "updated_at": "2025-04-26T20:46:14.000000Z"
            }
        ]
    }
}
```

