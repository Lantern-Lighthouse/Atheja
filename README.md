<div align="center">
  <h1>Atheja</h1>

  <p>
    <strong>A community-driven web search engine backend.</strong>
  </p>

  <p>
    <a href="https://github.com/Lantern-Lighthouse/Atheja/blob/main/LICENSE">
      <img src="https://img.shields.io/github/license/Lantern-Lighthouse/Atheja?style=for-the-badge" alt="License" />
    </a>
    <a href="https://github.com/Lantern-Lighthouse/Atheja/issues">
      <img src="https://img.shields.io/github/issues/Lantern-Lighthouse/Atheja?style=for-the-badge" alt="Issues" />
    </a>
  </p>
</div>

---

## 📖 About The Project

**Atheja** is the open-source backend system for a **community-powered web search engine**.  
Unlike traditional search engines that use automated crawlers, **Atheja lets users submit and manage websites themselves**, building a transparent, privacy-respecting, and community-driven search index.

This approach ensures that:
- Content is added **intentionally** by contributors, not automatically scraped.
- Search results remain **transparent** and **user-curated**.
- The community—not advertisers—guides the web discovery process.

Atheja is part of the **Lantern-Lighthouse initiative**, focused on developing open, collaborative, and ethical internet tools.

---

## ✨ Features

- 🔍 **User-Driven Indexing** – Websites are added and described by users, not crawlers.    
- 🧩 **Powered by F3 Cortex** – Uses [F3 Cortex](https://github.com/ikkez/f3-cortex) for database abstraction (ORM/ODM).  
- 🧠 **API-Based Architecture** – Provides REST-style JSON APIs for integration with frontends and tools.  
- 🗄️ **Database-Agnostic** – Works with MySQL, MariaDB, PostgreSQL, and SQLite.  
- 🌍 **Open Source & Transparent** – Licensed under GPL-3.0 and open for everyone to contribute.

---

## 🛠️ Built With

- [PHP 8.0+](https://www.php.net/)
- [F3 Cortex](https://github.com/ikkez/f3-cortex)
- [Composer](https://getcomposer.org/)


---

## 🚀 Getting Started

Follow these steps to set up Atheja locally for development or testing.

### ✅ Prerequisites

You’ll need the following installed:
- PHP ≥ 8.0  
- Composer  
- A local database server (MySQL, MariaDB, PostgreSQL, or SQLite)  
- Git

---

### ⚙️ Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Lantern-Lighthouse/Atheja.git
   cd Atheja
   ```

2.  **Configure your database:**
    Copy the example configuration file. This file stores your database credentials and is ignored by Git.
    ```sh
    cp app/Configs/db.ini.example app/Configs/db.ini
    ```
    Now, **edit `app/Configs/db.ini`** to match your local database username, password, and database name.

3.  **Install project dependencies:**
    This command downloads all the necessary PHP libraries (like the Fat-Free Framework) defined in `composer.json`.
    ```bash
    # If you have Composer installed globally (recommended)
    composer install
    
    # Or, use the local install script:
    php -r "copy('[https://getcomposer.org/installer](https://getcomposer.org/installer)', 'composer-setup.php');" && \
    php composer-setup.php && \
    php composer.phar install && \
    rm composer-setup.php composer.phar
    ```

4.  **Start the local development server:**
    This built-in PHP server is great for development.
    ```sh
    php --server="localhost:8081"
    ```
    Your project is now running at `http://localhost:8081`.

5.  **Initialize the database:**
    Visit the following URL in your browser or API client (like Postman or Insomnia). This endpoint runs the initial setup to create the necessary database tables.
    ```
    http://localhost:8081/api/db/init
    ```

You're all set! The backend is running and connected to your database.


## 👋 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you'd like to contribute, please:
1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

Don't forget to open an issue first to discuss any major changes!


## 📜 License

Distributed under the **GNU General Public License v3.0**. See `LICENSE` in the repository for more information.