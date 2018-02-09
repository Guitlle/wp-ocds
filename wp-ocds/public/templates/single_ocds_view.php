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
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<h4 class="center">NOG: <?php  echo $data->releases[0]->ocmp_extras->identification->NOG; ?></h4>
	<h4 class="center">SNIP: <?php  echo $data->releases[0]->ocmp_extras->identification->SNIP; ?></h4>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<h4>PRESUPUESTO ASIGNADO A LA OBRA:</h4>
	<h3 style="text-align: center;">Q <?php echo number_format($value, 2, ".", ","); ?></h3>
	<p style="text-align: center;">Monto para ejecutar la obra</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<h4 style="text-align: center;">FUENTE DE</h4>
	<h4 style="text-align: center;">FINANCIAMIENTO</h4>
	<h3 style="text-align: center;"><?php  echo $data->releases[0]->ocmp_extras->fuentefinanciamiento; ?></h3>
	<p style="text-align: center;">¿De dónde salió el dinero para la obra?</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<h4 style="text-align: center;">CONSTRUCTOR:</h4>
	<h3 style="text-align: center;"><?php  echo $data->releases[0]->awards[0]->suppliers[0]->name; ?></h3>
	<p style="text-align: center;">¿Quién está construyendo la obra?</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
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
		echo strftime("%A", strtotime($data->releases[0]->ocmp_extras->fecha_avances));
	}
	?></p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<h3 style="text-align: center;"><?php echo $data->releases[0]->ocmp_extras->progress->financial; ?>%</h3>
</div>
<?php endwhile; ?>
<?php endif; ?>


<?php get_footer(); ?>
