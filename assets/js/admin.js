// assets/js/admin.js
jQuery(function($){
    // Condicionais: mostrar caixas quando select = "sim"
    function toggleBoxes() {
        $('.pab-toggle').each(function(){
            const val = $(this).val();
            const target = $(this).data('target');
            if (!target) return;
            const showVal = $(target).data('show') || 'sim';
            if (val === showVal) $(target).show(); else $(target).hide();
        });
    }
    $(document).on('change', '.pab-toggle', toggleBoxes);
    toggleBoxes();
});
