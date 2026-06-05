# CloudSphere ☁️

A secure, serverless file storage web application built on Google Cloud Platform (GCP).  
Users can register, upload, download, organize, and delete files — with isolated storage per user and secure access controls.

---

## Features

- User registration and login system
- Upload, download, organize, and delete files
- 500 MB storage quota per user
- User-isolated folders (no user can access another's files)
- Time-limited signed URLs for secure file downloads
- Budget alerts and cloud monitoring configured

---

## Cloud Architecture

| Service | Purpose |
|---|---|
| Compute Engine | Hosts the web application |
| Cloud Storage | Stores uploaded user files |
| Firestore | NoSQL database for user data and file metadata |
| IAM | Access control and permission management |
| VPC | Isolated virtual network for security |
| Cloud Monitoring | Tracks usage and application health |
| Budget Alerts | Prevents unexpected cloud billing |

---

## Migration Note

This project was originally developed on **AWS** using EC2, S3, DynamoDB, and IAM.  
It was later migrated to **GCP** due to a key architectural difference:

- **DynamoDB (AWS):** Indexes are managed automatically
- **Firestore (GCP):** Composite indexes must be manually created to support query-based file uploads

This migration provided hands-on understanding of how NoSQL databases differ across cloud platforms.

---

## Tech Stack

- **Backend:** PHP
- **Database:** Google Cloud Firestore
- **Storage:** Google Cloud Storage
- **Infrastructure:** GCP (Compute Engine, VPC, IAM)

---

## Project Structure
