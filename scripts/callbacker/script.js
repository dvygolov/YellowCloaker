$(function () {
    function TemplateRefresh() {
        ModalRefresh();
    }

    $(window).resize(function () {
        TemplateRefresh();
    });
    TemplateRefresh();

    /* -----------------------------------------------------------------------------------------
     * Modal Refresh
     */
    function ModalRefresh() {
        if ($('.modal-callbacker').is(':visible')) {
            var modalBlock = $('.modal-callbacker:visible .modal-callbacker-block'),
                width = parseInt(modalBlock.width()),
                height = parseInt(modalBlock.height());
            if ($(window).height() > height + 20) {
                modalBlock.addClass('modal-callbacker-top').removeClass('margin-t-b-callbacker').css('margin-top', -1 * (height / 2));
            }
            else {
                modalBlock.addClass('margin-t-b-callbacker').removeClass('modal-callbacker-top');
            }
            if ($(window).width() > width) {
                modalBlock.addClass('modal-callbacker-left').removeClass('margin-l-callbacker').css('margin-left', -1 * (width / 2));
            }
            else {
                modalBlock.addClass('margin-l-callbacker').removeClass('modal-callbacker-left');
            }
        }
    }


    /* -----------------------------------------------------------------------------------------
     * Modal Show
     */
    $(document).on('click', 'a[modal]', function(){
        var modalWindow = $('div#' + $(this).attr('modal'));
        if (modalWindow.length){
            modalWindow.fadeIn('fast');
            $('body').addClass('modal-callbacker-show');
            ModalRefresh();
            return false;
        }
    });


    /* -----------------------------------------------------------------------------------------
     * Modal Hide
     */
    function ModalHide() {
        $('.modal-callbacker:visible').fadeOut('fast', function(){
            $('body').removeClass('modal-callbacker-show');
        });
    }

    $(document)
        .on('click', '.icon-close-callbacker, .modal-callbacker', function (event) {
            if (event.target != this)
                return false;
            else
                ModalHide();
        })
        .on('keydown', function (key) {
            if (key.keyCode == 27)
                ModalHide();
        })
        .on('click', '.modal-callbacker > *', function (event) {
            event.stopPropagation();
            return true;
        });


	try {
		setTimeout(
			function start_cb() {
                $('body').append('<div id="callbacker-design"><a href="#" modal="callbacker"><div class="callbacker-design-circle"></div><div class="callbacker-design-circle-fill"></div><div class="callbacker-design-img-circle"></div></a></div>');
			},
			3000 //Количество милисекунд до появления кнопки
		);
	}
	catch (e) {}
});