<<<<<<< HEAD
# YogaMart Website

This project is a PHP-based website for a yoga studio, featuring user registration with OTP verification, course listings, and an admin panel.

## Prerequisites

Before you begin, ensure you have the following software installed on your system:

*   **XAMPP:** A web server solution that includes Apache, MySQL, and PHP.
    *   [Download XAMPP](https://www.apachefriends.org/index.html)
*   **Composer:** A dependency manager for PHP.
    *   [Download Composer](https://getcomposer.org/download/)

## Setup Instructions

1.  **Get the Code:**
    *   Clone this repository or download the source code as a ZIP file.

2.  **Place the Project:**
    *   Move the project folder into the `htdocs` directory of your XAMPP installation (e.g., `C:\xampp\htdocs\YogaMart`).

    Navigate to the project directory (e.g., `cd C:\xampp\htdocs\YogaMart`).
    *   Run the following command to install the required PHP libraries:
        ```bash
        composer install
        ```

4.  **Set Up the Database:**
    *   Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.
    *   Open your web browser and go to `http://localhost/phpmyadmin/`.
    *   Click on the **New** button on the left sidebar to create a new database.
    *   Enter yogamart_db as the database name and click **Create**.
    *   Click on the newly created yogamart_db database in the left sidebar.
    *   Click on the **Import** tab at the top.
    *   Click **Choose File** and select the `yogamart_db.sql` file from the project directory.
    *   Click the **Go** button at the bottom of the page to import the database structure.

5.  **Configure the Environment:**
    *   In the project directory, find the file named `.env.example`.
    *   Make a copy of this file and rename it to `.env`.
    *   Open the `.env` file in a text editor and fill in the required credentials:
        *   `SECRET_KEY`: Generate a random secret key for hashing.
        *   `DB_PASSWORD`: Enter your MySQL database password (if you have one; it's blank by default in XAMPP).
        *   Fill in the `SMTP_*` settings with your email provider's details to enable OTP emails.

6.  **Media Files:**
    *   The `content` directory contains all the video and thumbnail files for the courses. This directory is included with the project so that the application will have sample data to display upon setup.

## Running the Application

1.  Ensure that **Apache** and **MySQL** are running from the XAMPP Control Panel.
2.  Open your web browser and navigate to `http://localhost/YogaMart/` (or whatever you named the project folder).
3.  The login and registration page should now be visible.

By following these steps, anyone can set up and run the project on their local machine without encountering version or missing software errors.
=======
# YogaMart-E-Learning-E-Commerce-PHP-project
>>>>>>> 27a1a1deec610b84359b728d74d62771c6d3014b
