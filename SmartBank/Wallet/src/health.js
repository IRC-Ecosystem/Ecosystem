export function health(_req, res) {
  return res.status(200).json({ status: 'ok', service: 'smartbank-wallet' });
}
