<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>安装<?php echo C('INSTALL_PRODUCT_NAME');?>－<?php echo ($meta_title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <link rel="stylesheet" type="text/css" href="/Public/css/pintuer.css">
    <script type="text/javascript" src="/Public/js/jquery-1.8.3.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="/Public/lib/layer/layer.js" charset="utf-8"></script>
 
    
</head>
<body style="background-color: #f6f6f6;">
	<div class="x12 bg-white border-bottom">
		<div class="container">
			<div class="height-large">
			<span><?php echo C('INSTALL_PRODUCT_NAME');?></span>
			</div>
		</div>
	</div>
    <div class="container">
        <div class="x12 padding bg-white margin-top">
            
	<div class="step">
		 <div class="step-bar  active" style="width:20%;"><span class="step-point icon-check"></span><span class="step-text">安装协议</span></span></div>
		 <div class="step-bar " style="width:20%;"><span class="step-point">2</span><span class="step-text">环境检测</span></div>
		 <div class="step-bar" style="width:20%;"><span class="step-point">3</span><span class="step-text">参数设置</span></div>
		 <div class="step-bar" style="width:20%;"><span class="step-point">4</span><span class="step-text">开始安装</span></div>
		 <div class="step-bar" style="width:20%;"><span class="step-point">5</span><span class="step-text">完成安装</span></div>
	</div>


    <div class="x12 padding">
        <div class="panel">
            <div class="panel-head">
                <span><?php echo C('INSTALL_PRODUCT_NAME');?> 安装协议</span>
            </div>
            <div class="panel-body">
                <p>感谢您选择<?php echo C('INSTALL_PRODUCT_NAME');?>，希望我们的努力能为您提供一个简单、高效、卓越的轻量级产品开发框架。</p>
                <p>用户须知：本协议是您与<?php echo C('INSTALL_COMPANY_NAME');?>之间关于您使用<?php echo C('INSTALL_PRODUCT_NAME');?>产品及服务的法律协议。无论您是个人或组织、盈利与否、用途如何（包括以学习和研究为目的），均需仔细阅读本协议，包括免除或者限制<?php echo C('INSTALL_COMPANY_NAME');?>责任的免责条款及对您的权利限制。请您审阅并接受或不接受本服务条款。如您不同意本服务条款及<?php echo C('INSTALL_COMPANY_NAME');?>随时对其的修改，您应不使用或主动取消<?php echo C('INSTALL_PRODUCT_NAME');?>产品。否则，您的任何对<?php echo C('INSTALL_PRODUCT_NAME');?>的相关服务的注册、登录、下载、查看等使用行为将被视为您对本服务条款全部的完全接受，包括接受<?php echo C('INSTALL_COMPANY_NAME');?>对服务条款随时所做的任何修改。</p>
                <p>本服务条款一旦发生变更, <?php echo C('INSTALL_COMPANY_NAME');?>将在官网上公布修改内容。修改后的服务条款一旦在网站公布即有效代替原来的服务条款。您可随时登陆官网查阅最新版服务条款。如果您选择接受本条款，即表示您同意接受协议各项条件的约束。如果您不同意本服务条款，则不能获得使用本服务的权利。您若有违反本条款规定，<?php echo C('INSTALL_COMPANY_NAME');?>有权随时中止或终止您对<?php echo C('INSTALL_PRODUCT_NAME');?>产品的使用资格并保留追究相关法律责任的权利。</p>
                <p>在理解、同意、并遵守本协议的全部条款后，方可开始使用<?php echo C('INSTALL_PRODUCT_NAME');?>产品。您也可能与<?php echo C('INSTALL_COMPANY_NAME');?>直接签订另一书面协议，以补充或者取代本协议的全部或者任何部分。</p>
                <p><?php echo C('INSTALL_COMPANY_NAME');?>拥有<?php echo C('INSTALL_PRODUCT_NAME');?>的全部知识产权，包括商标和著作权。<?php echo C('INSTALL_COMPANY_NAME');?>只允许您在遵守本协议各项条款的情况下复制、下载、安装、使用或者以其他方式受益于本软件的功能或者知识产权。</p>
				<div class="text-center">
					<a class="button bg-green" href="<?php echo U('step2');?>">同意安装协议</a>
					<a class="button bg-red" href="<?php echo C('INSTALL_WEBSITE_DOMAIN');?>">不同意</a>
				</div>
            </div>
            <div class="panel-foot">
                <span>版权所有 (c) 2014－<?php echo date("Y",time()); echo C('INSTALL_COMPANY_NAME');?> 保留所有权利。</span>
            </div>
        </div>
    </div>

        </div>
    </div>
</body>
</html>