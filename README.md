# Quenzy Question Paper Generator

![Quenzy Banner](https://placehold.co/1200x400?text=Quenzy+Question+Paper+Generator)

Welcome to Quenzy - an AI-powered question paper generator that helps educators create high-quality exams effortlessly. This guide will walk you through the setup and usage of Quenzy.

## Table of Contents
1. [XAMPP Installation](#1-xampp-installation)
2. [Python Installation](#2-python-installation)
3. [Python Library Installation](#3-python-library-installation)
4. [Starting Apache and MySQL](#4-starting-apache-and-mysql)
5. [Database Setup](#5-database-setup)
6. [Project Directory Structure](#6-project-directory-structure)
7. [PHP Mailer Configuration](#7-php-mailer-configuration)
8. [Application Usage](#8-application-usage-signuplogin)
9. [AI Features](#9-ai-powered-question-generator-details)

## 1. XAMPP Installation
XAMPP provides an Apache web server, MySQL database, and PHP, which are essential for running Quenzy.

- Download XAMPP from the [official website](https://www.apachefriends.org/index.html)
- Choose the appropriate installer for your OS (Windows, macOS, Linux)
- Run the installer (recommend default directory like `C:\xampp` on Windows)

## 2. Python Installation
Quenzy's AI features are powered by Python.

- Download Python from [python.org/downloads](https://www.python.org/downloads/)
- Choose the latest stable version (Python 3.9+)
- **Important:** Check "Add Python to PATH" during installation
- Verify installation:
  ```bash
  python --version
  # or
  python3 --version
  ```

## 3. Python Library Installation
Install required Python libraries:

```bash
pip install google-generativeai PyPDF2 mysql-connector-python python-dotenv
```

**Note:** Libraries like `os`, `sys`, and `datetime` are standard Python modules and don't require separate installation.

## 4. Starting Apache and MySQL
After installing XAMPP:

1. Open XAMPP Control Panel
2. Click "Start" next to Apache and MySQL
3. Wait for status indicators to turn green

## 5. Database Setup
Create and configure the Quenzy database:

1. Navigate to [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. **If exists:** Drop the existing `quenzy` database
3. Create new database named `quenzy`
4. Import the provided `quenzy.sql` file

## 6. Project Directory Structure
Place unzipped files in `C:\xampp\htdocs\` with this structure:

```
C:\xampp\htdocs\
└── quenzy/
    ├── auth/
    ├── dashboard/
    ├── database/
    ├── assets/
    ├── python_scripts/
    ├── includes/
    ├── quenzy.sql
    ├── index.php
    └── .env
```

## 7. PHP Mailer Configuration
For email functionalities:

1. Locate PHP Mailer config file (typically in `includes/phpmailer` or `database/config.php`)
2. Update SMTP server details and email credentials
3. Use an "app password" from your email provider (not your regular password)

## 8. Application Usage: Signup/Login
Access Quenzy:

1. Place files in `htdocs/quenzy` folder
2. Visit [http://localhost/quenzy](http://localhost/quenzy)
3. Sign up (new users) or log in (existing users)
4. Access dashboard to start generating question papers

## 9. AI-Powered Question Generator Details
Key AI features:

- **API Key Configuration:** Update Google Generative AI API key in `python_scripts/quegen.py`
- **Intelligent Generation:** Creates questions based on topics, difficulty, and types
- **Content Analysis:** Extracts key concepts from uploaded content
- **Customizable Output:** Control question count, complexity, and format
- **Review System:** Edit or delete questions before finalizing

## Conclusion
You're now ready to use Quenzy! Enjoy effortless question paper generation.

For issues or feedback, please contact our support channels.

This README:
1. Uses clean Markdown formatting
2. Includes all essential information from your HTML
3. Has proper section organization
4. Features code blocks for commands
5. Maintains a professional yet approachable tone
6. Includes placeholder for a banner image (replace with actual image)

You can copy this directly into a README.md file in your GitHub repository.
