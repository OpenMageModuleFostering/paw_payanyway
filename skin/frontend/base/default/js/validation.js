Validation.add('validate-rapidaphone', 'Please enter a valid phone number.', function(v) {
    return Validation.get('IsEmpty').test(v) || /^(\+)(\d|\s|\(|\)){10,20}$/.test(v);
});
Validation.add('validate-qiwiuser', 'Please enter a valid phone number.', function(v) {
    return Validation.get('IsEmpty').test(v) || /^[0-9]{10}$/.test(v);
});