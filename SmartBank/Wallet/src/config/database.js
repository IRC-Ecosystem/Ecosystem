import mysql from 'mysql2/promise';
import { config } from './config.js';

let pool;

try {
  pool = mysql.createPool({
    host: config.db.host,
    port: config.db.port,
    user: config.db.user,
    password: config.db.password,
    database: config.db.name,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    namedPlaceholders: false,
  });
  await pool.query('SELECT 1');
  console.log('Wallet connected to MySQL');
} catch (error) {
  console.error('FATAL: Wallet cannot connect to MySQL', error.message);
  process.exit(1);
}

export const db = {
  getDatabaseType: () => 'MySQL',
  query: async (text, params = []) => {
    let mysqlText = '';
    let inString = false;
    for (let index = 0; index < text.length; index += 1) {
      if (text[index] === "'") inString = !inString;
      if (!inString && text[index] === '$' && /\d/.test(text[index + 1] || '')) {
        mysqlText += '?';
        while (/\d/.test(text[index + 1] || '')) index += 1;
      } else {
        mysqlText += text[index];
      }
    }

    const [rows] = await pool.query(mysqlText, params);
    return {
      rows: Array.isArray(rows) ? rows : [],
      rowCount: Array.isArray(rows) ? rows.length : (rows.affectedRows || 0),
    };
  },
  getPool: () => pool,
};
