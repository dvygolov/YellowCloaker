<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="logo-pro">
                <a href="index.php">
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
                                            <a class="nav-link">Current Config:</a>
                                            <select style="color:black;">
                                                <?php
                                                $curConfig = $_GET['config']??"default";
                                                $allConfigNames = get_all_config_names();
                                                foreach ($allConfigNames as $configName) {
                                                    ?>
                                                    <option onclick="selectConfig('<?=$configName?>')"<?=($configName===$curConfig?" selected":"")?>><?=$configName?></option>
                                                    <?php
                                                }
                                                ?>

                                            </select>
                                            <a class="nav-link" href="" onclick="location.reload()">Refresh</a>
                                            <a class="nav-link" href="#" id='litepicker'>Date:</a>
                                            <a class="nav-link">
                                                <?php
                                                $calendsd = $_GET['startdate'] ?? '';
                                                $calended = $_GET['enddate'] ?? '';
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
    function selectConfig(configName){
        let url = new URL(window.location.href);
        let p = new URLSearchParams(window.location.search);
        p.set("config",configName);
        url.search = p.toString();
        window.location.href = url.href;
    }
</script>
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