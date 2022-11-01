<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="logo-pro">
                <a href="index.html">
                    <img class="main-logo" src="img/logo/logo.png" alt=""/>
                </a>
            </div>
        </div>
    </div>
</div>
<div class="header-advance-area">
    <div class="header-top-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="header-top-wraper">
                        <div class="row">
                            <div class="col-lg-1 col-md-0 col-sm-1 col-xs-12">
                                <div class="menu-switcher-pro">
                                    <button type="button" id="sidebarCollapse"
                                            class="btn bar-button-pro header-drl-controller-btn btn-info navbar-btn">
                                        <i class="icon nalika-menu-task"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-11 col-md-1 col-sm-12 col-xs-12">
                                <div class="header-right-info">
                                    <ul class="nav navbar-nav mai-top-nav header-right-menu">
                                        <li class="nav-item">
                                            <a class="nav-link"> Config:</a>
                                            <select>
                                                <?php
                                                $all_configs = get_all_configs();
                                                foreach ($all_configs as $config) {
                                                    ?>
                                                    <option><?=$config?></option>
                                                    <?php
                                                }
                                                ?>

                                            </select>
                                            <a class="nav-link" href="" onclick="location.reload()">Refresh</a>
                                            <a class="nav-link" href="#" id='litepicker'>Date:</a>
                                            <a class="nav-link">
                                                <?php
                                                $calendsd = isset($_GET['startdate']) ? $_GET['startdate'] : '';
                                                $calended = isset($_GET['enddate']) ? $_GET['enddate'] : '';
                                                if ($calendsd !== '' && $calended !== '') {
                                                    if ($calendsd === $calended) {
                                                        echo $calendsd;
                                                    } else {
                                                        echo "{
                                                    $calendsd
                                                    } - {
                                                    $calended
                                                    }";
                                                    }
                                                } else {
                                                    echo $startdate->format('d.m.y');

                                                } ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    let picker = new Litepicker({
        element: document.getElementById('litepicker'),
        format: 'DD.MM.YY',
        autoApply: false,
        lang: "ru-RU",
        buttonText: {"apply": "Выбрать", "cancel": "Отмена"},
        singleMode: false,
        setup: (p) => {
            p.on('button:apply', (date1, date2) => {
                var searchParams = new URLSearchParams(window.location.search);
                var d1 = moment(date1.dateInstance).format('DD.MM.YY');
                var d2 = moment(date2.dateInstance).format('DD.MM.YY');
                searchParams.set('startdate', d1);
                searchParams.set('enddate', d2);
                window.location.search = searchParams.toString();
            });
        }
    });
</script>
