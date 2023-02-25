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
                            <div class="col-lg-11 col-md-1 col-sm-12 col-xs-12">
                                <div>
                                </div>
                                <div class="header-right-info">
                                    <ul class="nav navbar-nav mai-top-nav header-right-menu">
                                        <li class="nav-item">
                                            <a class="nav-link">
                                                <i class="icon nalika-settings icon-wrap"></i>
                                                <span>Current Config:&nbsp;</span>
                                                <select style="color:black;" onchange="selectConfig(this.value)">
                                                    <?= get_config_menu() ?>
                                                </select>
                                            </a>
                                            <a class="nav-link" href="#" id='litepicker'>
                                                <i class="icon nalika-table icon-wrap"></i>
                                                <span>Date:&nbsp;&nbsp;<?= get_calend_date() ?></span>
                                            </a>
                                            <a class="nav-link" href="" onclick="location.reload()">
                                                <i class="icon nalika-refresh-button icon-wrap"></i>
                                                <span>Refresh</span>
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
    function selectConfig(configName) {
        let url = new URL(window.location.href);
        let p = new URLSearchParams(window.location.search);
        p.set("config", configName);
        url.search = p.toString();
        console.log(url.href);
        window.location.href = url.href;
    }
</script>
<script>
    flatpickr("#litepicker", {
        dateFomat: "DD.MM.YY",
        mode: "range",
        onClose: function (selectedDates, dateStr, instance) {
            update_datepicker_dates(selectedDates);
        }
    });

    function update_datepicker_dates(selectedDates) {
        function formatDate(date){
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            return `${day}.${month}.${year}`;
        }
        let searchParams = new URLSearchParams(window.location.search);
        let d1 = formatDate(selectedDates[0]);
        let d2 = formatDate(selectedDates[1]);
        searchParams.set('startdate', d1);
        searchParams.set('enddate', d2);
        window.location.search = searchParams.toString();
    }
</script>
<?php
function get_calend_date()
{
    global $startdate;
    $calendsd = $_GET['startdate'] ?? '';
    $calended = $_GET['enddate'] ?? '';
    if ($calendsd !== '' && $calended !== '')
        return $calendsd === $calended ? $calendsd : "{$calendsd} - {$calended}";
    else
        return $startdate->format('d.m.y');
}

function get_config_menu()
{
    $options = "";
    $curConfig = $_GET['config'] ?? "default";
    $allConfigNames = get_all_config_names();
    foreach ($allConfigNames as $configName) {
        $confSelected = $configName === $curConfig ? " selected" : "";
        $options .= "<option {$confSelected}>{$configName}</option>";
    }
    return $options;
}

?>
