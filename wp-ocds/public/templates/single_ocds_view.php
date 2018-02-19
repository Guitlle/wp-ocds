<?php
setlocale(LC_ALL,"es_ES");

$id = get_the_ID();

$thumbnail = get_the_post_thumbnail($id, "full");

$data = get_post_meta($id, "wp-ocds-record-data");
$id   = get_post_meta($id, "wp-ocds-record-id");

$data  = json_decode($data[0]);
$value = 0;
foreach( $data->releases[0]->awards as $adjudicacion ) {
	if ($adjudicacion->value->currency == "GTQ")
		$value  += floatval($adjudicacion->value->amount);
}

?>
<?php get_header(); ?>
<?php if (have_posts()) : ?>
<?php while (have_posts()) : the_post(); ?>
<div class="ocds-page-container">
	<?php echo $thumbnail; ?>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
    <?php the_title( '<h3>', '</h3>' ); ?>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<div class="wpb_text_column wpb_content_element ">
		<div class="wpb_wrapper">
			<h5 style="text-align: center; max-width: 600px; margin: auto;"><?php echo $data->releases[0]->ocmp_extras->descripcion; ?></h5>
		</div>
	</div>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<?php
	if (!empty($data->releases[0]->ocmp_extras->camaras)) {
	?>
	<div class="wpb_single_image wpb_content_element vc_align_center  element_from_fade element_from_fade_on"><div style="-webkit-animation-delay:0.1s; animation-delay:0.1s; -webkit-transition-delay:0.1s; transition-delay:0.1s">
		<div class="wpb_wrapper">

			<div class="vc_single_image-wrapper   vc_box_border_grey"><a href="<?php echo $data->releases[0]->ocmp_extras->camaras; ?>"><img class="vc_single_image-img " src="http://www.ojoconmipisto.com/open-contracting/wp-content/uploads/2018/01/icono-camara-125x125.png" width="125" height="125" alt="icono-camara" title="icono-camara"></a></div>
		</div></div>
	</div>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<div class="wpb_text_column wpb_content_element ">
		<div class="wpb_wrapper">
			<h5 style="text-align: center;">El artículo 80 de la Ley de Presupuesto 2017 establece<br>
que cuando los proyectos son mayores de Q900,000,<br>
la empresa contratada debe colocar un sistema de cámaras<br>
que permita a la población observar el avance de la obra.<br>
Haz click en el ícono de la cámara para ver la transmisión.</h5>

		</div>
	</div>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<?php
	}
 	?>
	<h4 class="center">NOG: <a href="http://guatecompras.gt/concursos/consultaConcurso.aspx?nog=<?php echo $data->releases[0]->ocmp_extras->identification->NOG; ?>&o=5"><?php  echo $data->releases[0]->ocmp_extras->identification->NOG; ?>  &nbsp;&nbsp;&nbsp;<i class="qode_icon_font_awesome fa fa-external-link  simple_social"></i></a></h4>
	<h4 class="center">SNIP: <?php  echo $data->releases[0]->ocmp_extras->identification->SNIP; ?></h4>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<h4>PRESUPUESTO <br> ASIGNADO A LA OBRA:</h4>
	<h3 style="text-align: center;">Q <?php echo number_format($value, 2, ".", ","); ?></h3>
	<p style="text-align: center;">Monto para ejecutar la obra</p>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<h4 style="text-align: center;">FUENTE DE</h4>
	<h4 style="text-align: center;">FINANCIAMIENTO</h4>
	<h3 style="text-align: center;"><?php  echo $data->releases[0]->ocmp_extras->fuentefinanciamiento; ?></h3>
	<p style="text-align: center;">¿De dónde salió el dinero para la obra?</p>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<h4 style="text-align: center;">CONSTRUCTOR:</h4>
	<h3 style="text-align: center;max-width: 600px;word-wrap: break-word; margin: auto;"><?php  echo $data->releases[0]->awards[0]->suppliers[0]->name; ?></h3>
	<p style="text-align: center;">¿Quién está construyendo la obra?</p>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
<?php if (property_exists($data->releases[0]->ocmp_extras, "vida_util") AND $data->releases[0]->ocmp_extras->vida_util != NULL AND $data->releases[0]->ocmp_extras->vida_util != "" ) { ?>
	<h4 style="text-align: center;">VIDA ÚTIL DE LA OBRA:</h4>
	<h3 style="text-align: center;"> <?php echo $data->releases[0]->ocmp_extras->vida_util; ?> </h3>
	<p style="text-align: center;">Tiempo que debe funcionar sin problemas</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
<?php } ?>
	<h4 style="text-align: center;">EJECUTADO:</h4>
<?php
if (property_exists($data->releases[0]->ocmp_extras->progress, "ejecutado") AND $data->releases[0]->ocmp_extras->progress->ejecutado != NULL AND $data->releases[0]->ocmp_extras->progress->ejecutado != "" ) {
	?>
	<h3 style="text-align: center;">Q <?php echo number_format(floatval($data->releases[0]->ocmp_extras->progress->ejecutado), 2, ".", ","); ?></h3>
<?php } ?>
	<p style="text-align: center;"><?php
	if (property_exists($data->releases[0]->ocmp_extras, "fecha_avances") AND $data->releases[0]->ocmp_extras->fecha_avances != NULL AND $data->releases[0]->ocmp_extras->fecha_avances != "" ) {
		echo strftime(" al %e de %B de %Y", strtotime($data->releases[0]->ocmp_extras->fecha_avances));
	}
	?></p>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 35px;height: 0px;"></div>
	<?php $afinancial = round($data->releases[0]->ocmp_extras->progress->financial); ?>
	<div class="q_progress_bar">
		<h5 class="progress_title_holder clearfix" style="">
			<span class="progress_number" style=""><span><?php echo $afinancial; ?></span>%</span>
		</h5>
		<div class="progress_content_outer" style="height: 50px;background-color: #323232;">
	    	<div data-percentage="<?php echo $afinancial; ?>" class="progress_content" style="height: 50px; background-color: rgb(255, 51, 0); width: <?php echo $afinancial; ?>%;"></div>
		</div>
	</div>
	<?php
	$startDate = $data->releases[0]->contracts[0]->period->startDate;
	$endDate = $data->releases[0]->contracts[0]->period->endDate;
	if ($startDate OR $endDate) {
	?>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 35px;height: 0px;"></div>
	<div class="vc_row wpb_row section vc_row-fluid " style=" text-align:left;">
		<div class=" full_section_inner clearfix">
			<div class="wpb_column vc_column_container vc_col-sm-6">
				<?php
				if ($startDate) {
				?>
				<h4 style="text-align: center;"><?php echo strftime("%e de %B <br> %Y", strtotime($startDate)); ?></h4>
				<h5 style="text-align: center;">INICIO</h5>
				<?php
				}
				?>
			</div>
			<div class="wpb_column vc_column_container vc_col-sm-6">
				<?php
				if ($endDate) {
				?>
				<h4 style="text-align: center;"><?php echo strftime("%e de %B <br> %Y", strtotime($endDate)); ?></h4>
				<h5 style="text-align: center;">FINAL</h5>
				<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 35px;height: 0px;"></div>
	<?php $afisico = round($data->releases[0]->ocmp_extras->progress->physical); ?>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 75px;"></div>
	<div class="wpb_wrapper">
		<h4 style="text-align: center;">AVANCE FÍSICO</h4>
		<h3 style="text-align: center;"><?php echo $afisico; ?>%</h3>
		<p style="text-align: center;">¿Qué porcentaje del proyecto se ha construido?</p>
	</div>
	<div class="separator  transparent   " style="margin-top: 0px;margin-bottom: 35px;height: 0px;"></div>
	<div class="q_progress_bar">
		<div class="progress_content_outer" style="height: 50px;background-color: #323232;">
	    	<div data-percentage="<?php echo round($afisico); ?>" class="progress_content" style="height: 50px; background-color: rgb(255, 51, 0); width: <?php echo $fisico; ?>%;"></div>
		</div>
	</div>

</div>
<?php endwhile; ?>
<?php endif; ?>


<?php get_footer(); ?>
