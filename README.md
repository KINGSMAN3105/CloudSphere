# CloudSphere ☁️

A secure, serverless file storage web application built on Google Cloud Platform (GCP).  
Users can register, upload, download, organize, and delete files — with isolated storage per user and secure access controls.

\---

## Features

* User registration and login system
* Upload, download, organize, and delete files
* 500 MB storage quota per user
* User-isolated folders (no user can access another's files)
* Time-limited signed URLs for secure file downloads
* Budget alerts and cloud monitoring configured

\---

## Cloud Architecture

|Service|Purpose|
|-|-|
|Compute Engine|Hosts the web application|
|Cloud Storage|Stores uploaded user files|
|Firestore|NoSQL database for user data and file metadata|
|IAM|Access control and permission management|
|VPC|Isolated virtual network for security|
|Cloud Monitoring|Tracks usage and application health|
|Budget Alerts|Prevents unexpected cloud billing|

\---

## Migration Note

This project was originally developed on **AWS** using EC2, S3, DynamoDB, and IAM.  
It was later migrated to **GCP** due to a key architectural difference:

* **DynamoDB (AWS):** Indexes are managed automatically
* **Firestore (GCP):** Composite indexes must be manually created to support query-based file uploads

This migration provided hands-on understanding of how NoSQL databases differ across cloud platforms.

\---

## Tech Stack

* **Backend:** PHP
* **Database:** Google Cloud Firestore
* **Storage:** Google Cloud Storage
* **Infrastructure:** GCP (Compute Engine, VPC, IAM)

\---

## Project Structure

```
CloudSphere/
├── register.php                 # User registration
├── login.php                    # User authentication
├── logout.php                   # Session management
├── upload.php                   # File upload handler
├── view.php                     # File listing and management
├── delete\\\_file.php              # File deletion
├── create\\\_all\\\_user\\\_folders.php  # Initializes user storage folders
└── includes/
    ├── db\\\_functions.php         # Firestore database operations
    └── signed\\\_url.php           # Generates time-limited signed URLs
```

\---

## Setup Instructions

> This project runs on a live GCP environment. To run it yourself:

1. Create a GCP project and enable: Compute Engine, Cloud Storage, Firestore, IAM
2. Clone this repository onto your Compute Engine instance
3. Install PHP and Composer on the instance
4. Run `composer install` to install dependencies (generates the `vendor/` folder)
5. In each file, replace the following placeholders with your actual values:

   * `YOUR\\\_BUCKET\\\_NAME`
   * `YOUR\\\_GCP\\\_PROJECT\\\_ID`
   * `YOUR\\\_FIRESTORE\\\_DATABASE`
6. Configure IAM roles and VPC firewall rules as needed
7. Access the app via the instance's public IP

\---

## Author

**\[Vaidik Parmar]**  
B.E. Information Technology  
\[http://www.linkedin.com/in/vaidik-parmar-746b63367] | \[https://github.com/KINGSMAN3105]

