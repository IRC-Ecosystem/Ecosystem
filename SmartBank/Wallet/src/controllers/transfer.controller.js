import { centralBankService } from '../services/centralBank.service.js';
import { responseHelper } from '../utils/response.js';

export const transferController = {

  // GET /api/v1/wallets/lookup?account_number=XXXXXXXXXX
  // Resolusi nomor rekening → holder name + wallet_id.
  // Dipakai前端 untuk konfirmasi tujuan transfer sebelum submit.
  // Tidak menampilkan saldo (mencegah enumeration).
  lookupRecipient: async (req, res, next) => {
    try {
      const accountNumber = String(req.query.account_number || '').replace(/\D/g, '');
      if (!/^\d{10}$/.test(accountNumber)) {
        return responseHelper.error(
          res,
          'BAD_REQUEST',
          'Nomor rekening tidak valid (harus 10 digit)',
          400
        );
      }
      const fromWalletId = req.user.walletId;
      const recipient = await centralBankService.getWalletByAccountNumber(accountNumber);
      if (recipient.wallet_id === fromWalletId) {
        return responseHelper.error(
          res,
          'BAD_REQUEST',
          'Tidak dapat melakukan transfer ke rekening Anda sendiri',
          400
        );
      }
      return responseHelper.success(res, {
        account_number: recipient.account_number,
        holder_name: recipient.holder_name,
        wallet_id: recipient.wallet_id,
      });
    } catch (err) {
      next(err);
    }
  },

  // POST /api/v1/transfers
  // Menerima tujuan transfer via `to_account_number` (disarankan) atau
  // `to_wallet_id` (legacy). Akan di-resolve dulu ke wallet_id sebelum
  // diteruskan ke Central Bank Core untuk settlement.
  createTransfer: async (req, res, next) => {
    try {
      const { to_wallet_id, to_account_number, amount, note } = req.body;
      const fromWalletId = req.user.walletId;
      const idempotencyKey = req.headers['idempotency-key'];

      // Basic field checks
      if ((!to_wallet_id && !to_account_number) || amount === undefined) {
        return responseHelper.error(
          res,
          'BAD_REQUEST',
          'Tujuan transfer (to_account_number / to_wallet_id) dan nominal (amount) wajib disertakan',
          400
        );
      }

      const amountText = String(amount);
      const transferAmount = Number(amountText);
      if (!/^\d+$/.test(amountText) || !Number.isSafeInteger(transferAmount) || transferAmount <= 0) {
        return responseHelper.error(
          res,
          'BAD_REQUEST',
          'Nominal transfer harus berupa angka bulat positif',
          400
        );
      }

      // Resolve tujuan transfer ke wallet_id.
      // Prioritas: to_account_number (public-facing) → to_wallet_id (legacy).
      let resolvedWalletId = to_wallet_id;
      if (to_account_number) {
        const sanitized = String(to_account_number).replace(/\D/g, '');
        if (!/^\d{10}$/.test(sanitized)) {
          return responseHelper.error(
            res,
            'BAD_REQUEST',
            'Nomor rekening tujuan tidak valid (harus 10 digit)',
            400
          );
        }
        const recipient = await centralBankService.getWalletByAccountNumber(sanitized);
        resolvedWalletId = recipient.wallet_id;
      }

      if (fromWalletId === resolvedWalletId) {
        return responseHelper.error(
          res,
          'BAD_REQUEST',
          'Tidak dapat melakukan transfer ke akun wallet Anda sendiri',
          400
        );
      }

      // Execute transfer through CentralBank Core
      const token = req.headers['authorization']?.split(' ')[1];
      const receipt = await centralBankService.transfer(
        fromWalletId,
        resolvedWalletId,
        transferAmount,
        note || '',
        idempotencyKey,
        token
      );
      console.log({
        timestamp: new Date().toISOString(),
        request_id: req.id,
        user_id: req.user.userId,
        action: transferAmount >= 10_000_000 ? 'large_transfer_completed' : 'transfer_completed',
        ip: req.ip,
        amount: transferAmount,
      });

      // Return successful receipt
      return responseHelper.success(res, {
        message: 'Transfer CBDC berhasil diselesaikan',
        receipt
      }, 200);

    } catch (err) {
      next(err);
    }
  }
};
