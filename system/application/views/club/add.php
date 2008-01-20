<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<? $page_title = "Add Club" ?>
<?php require APPPATH . "views/header.php"; ?>

<h1>Add Club</h1>

<?= $this->validation->error_string; ?>

<?= form_open("club/add/") ?>

<input type="text" name="name" value="<?= $this->validation->name ?>" size="16" /> Name <br/>
<input type="text" name="short_name" value="<?= $this->validation->short_name ?>" size="16" /> Abbreviation <br/>

<div><input type="submit" value="Add" /></div>

</form>

<?php require APPPATH . "views/footer.php"; ?>