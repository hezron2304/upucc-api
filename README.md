# UPCC API Framework

## 🚀 Project Overview
**UPCC API Framework** is a robust, custom-built RESTful API designed to power the ecosystem of *Unit Kegiatan Mahasiswa (UKM) Programming*. Built on a native **MVC (Model-View-Controller)** architecture with **OOP (Object-Oriented Programming)** principles in PHP 8, this framework delivers enterprise-grade performance without the overhead of heavy third-party libraries.

This API serves as the backbone for the frontend application, managing critical organizational functions including Membership, Events, Inventory, Finance, and E-Library activities.

---

## 🛡️ Key Features & Security Layers

We have implemented a defense-in-depth security strategy to ensure data integrity and protection against common web vulnerabilities.

### 1. Security Core
*   **JWT Authentication (Stateless)**: Transitioned from stateful sessions to **JSON Web Tokens (JWT)**. Verification is stateless, allowing for infinite scalability across servers.
    *   *Implementation*: `App\Core\Auth::user()` verifies signatures (HMAC SHA256) and expiration times securely.
*   **Bcrypt Password Hashing**: User passwords are never stored in plain text. We utilize the **Bcrypt** algorithm (cost factor 10) for industry-standard credential protection.
*   **Prepared Statements (Anti-SQL Injection)**: ALL database queries utilize `mysqli` prepared statements with parameter binding. This completely mitigates SQL Injection attacks by separating SQL logic from data.
*   **Environment Security**: Vital configuration secrets (DB credentials, JWT Secrets) are stored in `env_settings.php`, which is **blocked from public access** via `.htaccess`.

### 2. Data Integrity & Audit
*   **Atomic Database Updates**: To prevent **Race Conditions** in high-concurrency scenarios (e.g., simultaneous item borrowing), we use atomic SQL queries:
    ```sql
    UPDATE inventory SET stok = stok - ? WHERE id = ? AND stok >= ?
    ```
    This ensures stock never drops below zero, even if multiple users click "Pinjam" simultaneously.
*   **Activity Logging (Audit Trail)**: A centralized logging system records critical actions (Login, Create, Delete, Update) into the `logs` table, ensuring accountability.

---

## 📂 Directory Structure

Our architecture separates the **Public Interface** (API Endpoints) from the **Business Logic** (App Core).

```
/upucc-api
├── app/
│   ├── Core/               # Framework Kernel
│   │   ├── Auth.php        # JWT Handling & User Session
│   │   ├── Database.php    # Singleton Database Connection
│   │   ├── BaseController.php # Access Control & Input Sanitization
│   │   └── Response.php    # Standardized JSON Output
│   ├── Controllers/        # Business Logic & Request Handling
│   │   ├── AuthController.php
│   │   ├── InventoryController.php
│   │   └── ... (Specific Feature Controllers)
├── env_settings.php        # [PROTECTED] Environment Variables
├── config.php              # Bootstrapper & Error Handling
├── .htaccess               # Security Rules & Rewrite Engine
├── index.php               # Silence is golden
└── *.php                   # Public API Endpoints (Entry Points)
    ├── login.php
    ├── anggota.php
    ├── inventory.php
    └── ...
```

---

## 🔐 Authentication Guide (Frontend Team)

**IMPORTANT**:
Apart from `login.php` and `register.php`, **ALL** requests to this API must include the JWT Token obtained upon successful login.

**Header Format:**
```http
Authorization: Bearer <YOUR_JWT_TOKEN>
X-Content-Type-Options: nosniff
```

Requests without this header or with an expired token will receive `401 Unauthorized`.

---

## 👥 Role-Based Access Control (RBAC)

The API enforces strict access control based on the user's `jabatan` (role):

| Role | Access Level |
| :--- | :--- |
| **Super Admin** | **God Mode**. Full access to all modules, including viewing system `Logs` and managing all data. |
| **Admin** | **Manager**. Can manage Anggota, Inventory, Feedback, and approve/reject content. |
| **Humas** | **Public Relations**. Manages `Pengumuman`, `Materi`, and `Portofolio`. Can approve uploaded materials. |
| **Bendahara** | **Finance**. Exclusive write access to `Kas` (Cash Flow) management. |
| **Sekretaris** | **Administration**. Exclusive rights to create and manage `Events`. |
| **Anggota** | **User**. Read-only mostly. Can perform `Absensi`, `Vote`, `Borrow Item`, and view data. |

---

## 📡 API Endpoints Documentation

### 1. Authentication (`auth`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `POST` | `/login.php` | Public | Login user via Email/Username & Password. Returns **JWT Token**. |
| `POST` | `/register.php` | Public | Register new member (Default role: *Anggota*). |
| `POST` | `/logout.php` | **Bearer** | Logout user (Logs activity server-side). |

### 2. User Profile (`profil.php`, `update_password.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/profil.php` | **Bearer** | Get currently logged-in user profile. |
| `POST` | `/profil.php` | **Bearer** | Update profile data (Email, Prodi). |
| `PUT` | `/update_password.php` | **Bearer** | Change account password. |

### 3. Anggota (`anggota.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/anggota.php` | **Bearer** | List all registered members. |
| `POST` | `/anggota.php` | **Admin** | Add a new member manually (Bypass register). |
| `DELETE` | `/anggota.php` | **Admin** | Delete a member permanently. |

### 4. Inventory & Peminjaman (`inventory.php`, `peminjaman.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/inventory.php` | **Bearer** | List all available equipment/tools. |
| `POST` | `/inventory.php` | **Admin** | Add new item to inventory. |
| `DELETE` | `/inventory.php` | **Admin** | Remove item from inventory. |
| `GET` | `/peminjaman.php` | **Bearer** | View transaction history (User sees own, Admin sees all). |
| `POST` | `/peminjaman.php` | **Bearer** | **[Atomic]** Borrow an item (Reduces stock safely). |
| `PUT` | `/peminjaman.php` | **Admin** | Process item return & restore stock. |

### 5. Materi & E-Library (`materi.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/materi.php` | **Bearer** | List learning materials. (Anggota: 'ACC' only, Admin/Humas: All). |
| `POST` | `/materi.php` | **Bearer** | Upload material. (Admin/Humas: Auto-ACC, Anggota: Pending). |
| `PUT` | `/materi.php` | **Humas+** | Approve (`acc`) or Reject (`reject`) uploaded material. |
| `DELETE` | `/materi.php` | **Admin** | Delete material. |

### 6. Event & Absensi (`event.php`, `absensi.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/event.php` | **Bearer** | List upcoming and past events. |
| `POST` | `/event.php` | **Sekretaris+** | Create a new event schedule. |
| `GET` | `/absensi.php?id_event=X`| **Bearer** | List attendees for a specific event. |
| `POST` | `/absensi.php` | **Bearer** | Check-in/Attendance for an event. |

### 7. Keuangan (`kas.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/kas.php` | **Bearer** | View cash flow report and total balance. |
| `POST` | `/kas.php` | **Bendahara+** | Record cash In (`Masuk`) or Out (`Keluar`). |

### 8. Informasi & Karya (`pengumuman.php`, `portofolio.php`, `divisi.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/pengumuman.php` | **Bearer** | List announcements. |
| `POST` | `/pengumuman.php` | **Humas+** | Create new announcement. |
| `DELETE` | `/pengumuman.php` | **Humas+** | Delete announcement. |
| `GET` | `/portofolio.php` | **Bearer** | Showcase member projects/works. |
| `POST` | `/portofolio.php` | **Bearer** | Add project to portfolio. |
| `GET` | `/divisi.php` | **Bearer** | List available divisions. |

### 9. Feedback & Voting (`feedback.php`, `voting.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/feedback.php` | **Admin** | Read member suggestions/feedback. |
| `POST` | `/feedback.php` | Public | Send anonymous feedback/suggestion. |
| `GET` | `/voting.php` | **Bearer** | View active voting topics and results. |
| `POST` | `/voting.php` | **Bearer** | Cast a vote on a topic. |

### 10. System Logs (`logs.php`)
| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/logs.php` | **Super Admin** | View system-wide activity audit trail. |

---

## 💻 Installation & Setup

1.  **Database Setup**:
    *   Create a database (e.g., `upucc_db`).
    *   Import the provided `.sql` schema file.
2.  **Configuration**:
    *   Locate `env_settings.php` (or create one outside the web root for better security).
    *   Configure your credentials:
        ```php
        DB_HOST=localhost
        DB_USER=
        DB_PASS=
        DB_NAME=
        JWT_SECRET=YourSuperSecretLongKeyHere
        ```
    *   Ensure `config.php` points to the correct `env_settings.php` path.
3.  **Server Config**:
    *   Ensure `mod_rewrite` is enabled in Apache.
    *   The `.htaccess` file provided handles security headers and routing protection. **Do not remove it.**
4.  **Testing**:
    *   Access `/index.php`. If configured correctly, it should be silent or return a generic response. Try POSTing to `/login.php`.

---

## 🚦 HTTP Status Codes Reference

We use standard HTTP codes to indicate API response status.

*   `200 OK`: Request successful.
*   `201 Created`: Resource successfully created (e.g., New Member, New Item).
*   `400 Bad Request`: Validation failed (Missing input, invalid data type).
*   `401 Unauthorized`: Token missing, invalid, or expired.
*   `403 Forbidden`: Authenticated, but you do not have the required Role access.
*   `404 Not Found`: Endpoint or Resource ID not found.
*   `409 Conflict`: Duplicate entry (e.g., Username exists, Already Voted).
*   `500 Internal Server Error`: Something went wrong on the server side (Check Logs).

---

