# How to Run YogaMart with Docker 🐳

This guide explains how to containerize this website, connect it to a MySQL database, and run it on any other PC using Docker.

## 1. How it Works (The Logic)
When using Docker, we run the **Website** and the **Database** in two separate containers:
- **`yogamart_app`**: Contains PHP 8.1, Apache, and your website code.
- **`yogamart_db`**: Contains the MySQL server and your data.

### How they connect:
In a standard XAMPP setup, your PHP connects to `localhost`. In Docker, `localhost` refers to the container itself, not the database. 
- We use a **Docker Network** (`yogamart_network`) to link them.
- Inside the network, the PHP container connects to the database using the hostname **`db`** (the name of the service in `docker-compose.yml`) instead of `localhost`.

---

## 2. Prerequisites
The other PC must have:
1. **Docker Desktop** installed.
2. **Git** (to clone the code).

---

## 3. Step-by-Step Setup

### Step 1: Prepare the Project
Ensure you have the following files in your root:
- `Dockerfile` (Builds the PHP image)
- `docker-compose.yml` (Coordinates the app and db)
- `db-image/Dockerfile` (Builds the MySQL image)
- `db-image/yogamart_db.sql` (Your database backup)

### Step 2: Build and Run
Open a terminal in the project folder and run:
```bash
docker-compose up -d --build
```
- `--build`: Forces Docker to rebuild the images with your latest code/SQL.
- `-d`: Runs it in "detached" mode (background).

### Step 3: Access the Website
Once the containers are running, open your browser and go to:
**`http://localhost:8081`**

---

## 4. How to move to another PC?
1. **Copy the entire folder** to the other PC (or push it to GitHub and clone it).
2. **Install Docker** on that PC.
3. Open terminal in the folder and run `docker-compose up -d`.
4. **That's it!** Docker will automatically:
   - Setup PHP and Apache.
   - Setup MySQL.
   - Import your `yogamart_db.sql` automatically on the first start.
   - Connect them together.

---

## 5. Persistence (Your Data)
- **Database:** Stored in a Docker volume called `mysql_data`. Even if you stop the containers, your orders and users won't be deleted.
- **Uploads:** The `./content` folder is mapped to the container. Any images you upload while running Docker will appear in your local `content/` folder.

## 6. How to Share Your Image (Docker Hub)
If you want others to download your image from the internet (like a mobile app):

1. **Create a Docker Hub account** at [hub.docker.com](https://hub.docker.com/).
2. **Login** in your terminal:
   ```bash
   docker login
   ```
3. **Tag your local image** (Replace `yourusername` with your Docker Hub username):
   ```bash
   docker tag yogamart_app:latest yourusername/yogamart-app:latest
   ```
4. **Push the image**:
   ```bash
   docker push yourusername/yogamart-app:latest
   ```
5. **Others can then run it** by just pulling it:
   ```bash
   docker pull yourusername/yogamart-app:latest
   ```
   *Note: They will still need the `docker-compose.yml` file to link it to the database.*

---

## 🔧 Troubleshooting
- **Database not connecting?** Check the `includes/connect.php` file. It must use `getenv('DB_HOST')` which Docker sets to `db`.
- **Permission issues?** The Dockerfile automatically runs `chown` to make the `content/` and `uploads/` folders writable for Apache.
- **Common Warnings:** The `Dockerfile` now includes `gd` and `pdo_mysql` extensions to prevent typical PHP errors when running in a container.
