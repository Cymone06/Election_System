# STVC Election System

A comprehensive, secure, and user-friendly web-based platform designed to manage student government elections at Seme Technical and Vocational College (STVC). The system ensures transparency, fairness, and active participation in the democratic process.

## Key Features

- **Secure Registration & Login**: 
  - A robust registration system for students.
  - **Admin Approval Required**: New student accounts are set to `pending` and must be manually approved by an administrator, enhancing security and data integrity.
  - Show/hide password toggles for improved usability.
  - Secure password hashing using modern PHP standards.

- **Role-Based Access Control**:
  - **Student**: Can view election details, apply for positions, vote in active elections, and view results.
  - **Admin**: Manages the election lifecycle, applications, user accounts, and content.
  - **Super Admin**: Has full control, including the ability to approve/reject new admin accounts.

- **Comprehensive Admin Dashboard**:
  - **Election Management**: Create, view, and update election periods (set status to `Upcoming`, `Active`, `Ended`, or `Deleted`).
  - **Account Management**: View all student and admin accounts. Approve or reject pending student registrations.
  - **Application Vetting**: Review and manage candidate applications (approve, reject, vet).
  - **Content Management**: Update the photo gallery and news sections.
  - **Data Recovery**: A "soft-delete" system for elections and gallery items, allowing an admin to restore accidentally deleted records.
  - **System Controls**: Manually open or close the student application portal.

- **Dynamic Voting & Results**:
  - **Homepage Carousel**: A live, auto-updating carousel on the homepage shows real-time election results when an election is active.
  - **Live Results Page**: A dedicated page for live results that fetches data periodically without needing a page refresh. Features a countdown timer for the election's end.
  - **Secure Voting**: An intuitive and secure interface for students to cast their votes.

- **Reporting**:
  - Administrators can select a past election and generate a comprehensive report in **Microsoft Word (`.doc`)** format, including voter turnout and a detailed breakdown of results.

## Technologies Used

- **Backend**: PHP
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Frameworks/Libraries**:
  - [Bootstrap 5](https://getbootstrap.com/): For responsive design and UI components.
  - [Font Awesome](https://fontawesome.com/): For icons.
  - [AOS (Animate On Scroll)](https://michalsnik.github.io/aos/): For scroll animations.
  - [DataTables.js](https://datatables.net/): For advanced table sorting, searching, and pagination in the admin dashboard.
  - [jQuery](https://jquery.com/): As a dependency for Bootstrap and DataTables.

## Project Setup

1.  **Prerequisites**: Make sure you have a local server environment like [XAMPP](https://www.apachefriends.org/index.html), WAMP, or MAMP installed.

2.  **Clone the Repository**:
    ```bash
    git clone <your-repository-url> "Election System"
    ```

3.  **Database Setup**:
    -   Open your database management tool (e.g., phpMyAdmin).
    -   Create a new database (e.g., `election_system`).
    -   Import the `General/config/database.sql` file into the newly created database. This will set up all the necessary tables.

4.  **Configuration**:
    -   Navigate to `General/config/` and open the `connect.php` file.
    -   Update the database credentials (`$servername`, `$username`, `$password`, `$dbname`) to match your local environment setup.

5.  **Create Super Admin**:
    -   To create the first administrator account, navigate to `http://localhost/Election%20System/General/setup_super_admin.php` in your web browser.
    -   Follow the on-screen instructions to create your super admin account.
    -   **Important**: For security, delete the `setup_super_admin.php` file after you have created your account.

6.  **Run the Application**:
    -   Place the project folder inside your server's root directory (e.g., `htdocs` for XAMPP).
    -   Access the student-facing site via: `http://localhost/Election%20System/General/`
    -   Access the admin login via: `http://localhost/Election%20System/General/Admin/admin_login.php`

--- 