/**
 * Select2 Indonesian translation.
 * 
 * Author: Ibrahim Yusuf <ibrahim7usuf@gmail.com>
 */
(function ($) {
    "use strict";

    $.extend($.fn.select2.defaults, {
        formatNoMatches: function () { return "Tidak ada data yang sesuai"; },
        formatInputTooShort: function (input, min) { var n = min - input.length; return "Masukkan " + n + " huruf lagi" + (n == 1 ? "" : ""); },
        formatInputTooLong: function (input, max) { var n = input.length - max; return "Hapus " + n + " huruf" + (n == 1 ? "" : ""); },
        formatSelectionTooBig: function (limit) { return "Anda hanya dapat memilih " + limit + " pilihan" + (limit == 1 ? "" : ""); },
        formatLoadMore: function (pageNumber) { return "Mengambil data..."; },
        formatSearching: function () { return "Mencari..."; }
    });
})(jQuery);
