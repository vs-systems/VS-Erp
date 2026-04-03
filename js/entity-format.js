/**
 * VS System ERP - Entity Formatting Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-Uppercase (Except email)
    document.querySelectorAll('input:not([type="email"]):not(.no-upper), textarea').forEach(input => {
        input.addEventListener('input', (e) => {
            const start = e.target.selectionStart;
            const end = e.target.selectionEnd;
            
            // Convert to Upper and Remove Accents
            let value = e.target.value.toUpperCase();
            value = value.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); 
            
            e.target.value = value;
            e.target.setSelectionRange(start, end);
        });
    });

    // 2. CUIT Mask (00-00000000-0)
    const cuitInputs = document.querySelectorAll('.mask-cuit');
    cuitInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').substring(0, 11);
            let formatted = '';
            if (val.length > 0) formatted += val.substring(0, 2);
            if (val.length > 2) formatted += '-' + val.substring(2, 10);
            if (val.length > 10) formatted += '-' + val.substring(10, 11);
            e.target.value = formatted;
        });
    });

    // 3. Document Mask (00.000.000)
    const dniInputs = document.querySelectorAll('.mask-dni');
    dniInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').substring(0, 8);
            let formatted = '';
            if (val.length > 0) formatted += val.substring(0, 2);
            if (val.length > 2) formatted += '.' + val.substring(2, 5);
            if (val.length > 5) formatted += '.' + val.substring(5, 8);
            e.target.value = formatted;
        });
    });
});
