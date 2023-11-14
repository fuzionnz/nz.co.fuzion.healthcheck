SELECT COUNT(*) as count,DATE(receive_date) as receive_date, contact_type,
IF(contribution_recur_id is NULL, 0, 1) as recurring,
  financial_type_id as financial_type, SUM(total_amount) as total, payment_instrument_id as instrument from civicrm_contribution JOIN civicrm_contact as contact ON contact_id=contact.id  WHERE receive_date is not null AND ( is_test = 0 OR is_test = NULL) additional_where group by DATE(receive_date),contact.contact_type, instrument,financial_type_id,IF(contribution_recur_id is NULL, 0, 1);
