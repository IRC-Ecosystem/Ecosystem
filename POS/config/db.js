const mysql = require("mysql2");
require("dotenv").config();

const db = mysql.createPool({
  host: process.env.DB_HOST,
  port: Number(process.env.DB_PORT) || 3306,
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0,
  connectionLimit: Number(process.env.DB_POOL_SIZE) || 10,
  waitForConnections: true,
  queueLimit: 0
});

module.exports = db;
