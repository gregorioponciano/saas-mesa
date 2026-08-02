<script>
    window.applyCpfMask = function (value) {
        let v = (value || '').replace(/\D/g, '').substring(0, 11);
        if (v.length <= 3) return v;
        if (v.length <= 6) return v.substring(0, 3) + '.' + v.substring(3);
        if (v.length <= 9) return v.substring(0, 3) + '.' + v.substring(3, 6) + '.' + v.substring(6);
        return v.substring(0, 3) + '.' + v.substring(3, 6) + '.' + v.substring(6, 9) + '-' + v.substring(9);
    };

    window.isValidCpf = function (value) {
        const c = (value || '').replace(/\D/g, '');
        if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
        let sum = 0;
        for (let i = 0; i < 9; i++) sum += parseInt(c[i]) * (10 - i);
        let d1 = (sum * 10) % 11;
        if (d1 === 10) d1 = 0;
        if (d1 !== parseInt(c[9])) return false;
        sum = 0;
        for (let i = 0; i < 10; i++) sum += parseInt(c[i]) * (11 - i);
        let d2 = (sum * 10) % 11;
        if (d2 === 10) d2 = 0;
        return d2 === parseInt(c[10]);
    };
</script>
