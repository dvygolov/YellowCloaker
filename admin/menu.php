<?php
    $menuPassword = $_GET['password'];
    $menuConfig = $_GET['config']??'default';
    $menuQueryString = "password={$menuPassword}&config={$menuConfig}{$date_str}";
?>
<div class="left-sidebar-pro">
    <nav id="sidebar" class="">
        <div class="sidebar-header">
            <a href="/admin/index.php?<?=$menuQueryString?>">
                <img class="main-logo" src="img/logo/logo.png" alt=""/>
            </a>
            <strong>
                <img src="img/favicon.png" alt="" style="width:50px"/>
            </strong>
        </div>
        <div class="nalika-profile">
            <div class="profile-dtl">
                <a href="https://t.me/yellow_web">
                    <img src="img/notification/4.jpg" alt=""/>
                </a>
                <?php include "version.php" ?>
            </div>
        </div>
        <div class="left-custom-menu-adp-wrap comment-scrollbar">
            <nav class="sidebar-nav left-sidebar-menu-pro">
                <ul class="metismenu" id="menu1">
                    <li class="active">

                        <a class="has-arrow"
                           href="index.php?<?=$menuQueryString?>"
                           aria-expanded="false">
                            <i class="icon nalika-bar-chart icon-wrap"></i>
                            <span class="mini-click-non">Traffic</span>
                        </a>
                        <ul class="submenu-angle" aria-expanded="false">
                            <li>
                                <a title="Statistics"
                                   href="statistics.php?<?=$menuQueryString?>">
                                    <span class="mini-sub-pro">Statistics</span>
                                </a>
                            </li>
                            <li>
                                <a title="Allowed"
                                   href="index.php?<?=$menuQueryString?>">
                                    <span class="mini-sub-pro">Allowed</span>
                                </a>
                            </li>
                            <li>
                                <a title="Leads"
                                   href="index.php?filter=leads&<?=$menuQueryString?>">
                                    <span class="mini-sub-pro">Leads</span>
                                </a>
                            </li>
                            <li>
                                <a title="Blocked"
                                   href="index.php?filter=blocked&<?=$menuQueryString?>">
                                    <span class="mini-sub-pro">Blocked</span>
                                </a>
                            </li>
                            <li>
                                <a title="Bottom button" href="#bottom">
                                    <span class="mini-sub-pro">Go to bottom</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a class="has-arrow"
                           href="editsettings.php?<?=$menuQueryString?>"
                           aria-expanded="false">
                            <i class="icon nalika-table icon-wrap"></i>
                            <span class="mini-click-non">Configuration</span>
                        </a>
                        <ul class="submenu-angle" aria-expanded="false">
                            <li>
                                <a title="Add configuration"
                                   href="statistics.php?<?=$menuQueryString?>">
                                    <span class="mini-sub-pro">Add Config</span>
                                </a>
                            </li>
                            <li>
                                <a title="Delete configuration"
                                   href="index.php?<?=$menuQueryString?>">
                                    <span class="mini-sub-pro">Delete Config</span>
                                </a>
                            </li>
                    </li>
                </ul>
            </nav>
        </div>
    </nav>
</div>