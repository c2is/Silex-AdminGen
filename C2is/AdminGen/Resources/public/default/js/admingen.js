$(document).ready(function() {
    $(".component .form-btn-submit").bind("click", function (e) {
        var base = $(this);

        var contentComponent = base
            .parent(".component-footer")
            .parent(".component")
            .children(".component-content");

        $("form", contentComponent).submit();
    });

    $(".actions-item .delete").confirmModal();

    $("*[required]").not("[type=submit]").jqBootstrapValidation();

    $("select").width($("select").width()).select2();
});
