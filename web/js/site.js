// сайдбар: переключатель collapsed
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebarMenu');
    btn && btn.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
    });
  
    // multi-select toggle без Ctrl
    document.querySelectorAll('select[multiple] option').forEach(function(opt) {
      opt.addEventListener('mousedown', function(e) {
        e.preventDefault();
        opt.selected = !opt.selected;
        opt.parentElement.dispatchEvent(new Event('change'));
      });
    });
  });
  