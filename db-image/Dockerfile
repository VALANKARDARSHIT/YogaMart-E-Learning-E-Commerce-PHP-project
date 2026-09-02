# Using the Oracle-based image which is ~200MB smaller than the default Debian one
FROM mysql:8.0-oracle

# Copy the SQL file to initialize the database
COPY yogamart_db.sql /docker-entrypoint-initdb.d/
