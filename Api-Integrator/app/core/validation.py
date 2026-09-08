def validasi_amount(raw_amount):
    try:
        amount = float(raw_amount)
    except (TypeError, ValueError):
        return False, "Amount harus berupa angka (contoh: 50000).", 0.0
    if amount < 0:
        return False, "Amount tidak boleh negatif.", 0.0
    if amount > 1_000_000_000:
        return False, "Amount melebihi batas maksimal sistem (Rp 1.000.000.000).", 0.0
    return True, "OK", amount

