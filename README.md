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

Atheja is the open-source backend system for a community-driven web search engine. Built with PHP and the lightweight Fat-Free Framework, it provides the core API and logic for crawling, indexing, and retrieving search results.

This project is part of the Lantern-Lighthouse initiative to create transparent and community-powered web tools.

## 📖 Table of Contents

* [About The Project](#about-the-project)
* [✨ Features](#-features)
* [🛠️ Built With](#️-built-with)
* [🚀 Getting Started](#-getting-started)
    * [Prerequisites](#prerequisites)
    * [Installation](#installation)
* [👋 Contributing](#-contributing)
* [📜 License](#-license)

## About The Project

The goal of Atheja is to provide a scalable and open-source alternative to traditional search engine backends. By being community-driven, we aim to build a search index that is transparent, privacy-respecting, and guided by its users, not by advertisers.

## ✨ Features

* **Lightweight & Fast:** Built on the [Fat-Free Framework](https://fatfreeframework.com/3/) for high performance.
* **API-Driven:** Designed as a JSON-based API backend, perfect for any modern web frontend.
* **Database Agnostic:** Easily configurable to work with MySQL, PostgreSQL, SQLite, and more.
* **Open Source:** Fully transparent and open to community contributions.

## 🛠️ Built With

* [PHP](https://www.php.net/) (v8.0 or newer)
* [Fat-Free Framework](https://fatfreeframework.com/3/)
* [Composer](https://getcomposer.org/)

## 🚀 Getting Started

To get a local copy up and running for development, follow these simple steps.

### Prerequisites

Make sure you have the following software installed on your system:
* [PHP](https://www.php.net/manual/en/install.php) (v8.0+)
* [Composer](https://getcomposer.org/doc/00-intro.md)
* A local database server (e.g., MySQL, MariaDB, PostgreSQL)

### Installation

1.  **Clone the repository:**
    ```sh
    git clone [https://github.com/Lantern-Lighthouse/Atheja.git](https://github.com/Lantern-Lighthouse/Atheja.git)
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