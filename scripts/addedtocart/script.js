$(document).ready(function(){
    if(window.innerWidth > 600) {
        var i = 0;

        function application_block() {
            i = 1;
            $('.application_block:nth-child(' + i + ')').fadeIn(500).delay(7000).fadeOut(500);//В этой строчке в мс 500 - время анимации появления, 7000 - время задержки, 500 - время затухания уведомления соответсвенно
        }

        setTimeout(function () {
            setInterval(
                function () {
                    i = i + 1;
                    if (i > 10) i = 1;//10 - количество уведомлений
                    $('.application_block:nth-child(' + i + ')').fadeIn(500).delay(7000).fadeOut(500);//В этой строчке в мс 500 - время анимации появления, 7000 - время задержки, 500 - время затухания уведомления соответсвенно
                }, 30000);//30000 - задержка в мс меду показами уведомлений
            application_block();
        }, 8000);//8000 - задержка в мс перед показом первого уведомления
    }
});