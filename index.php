<?php

namespace Syltaen;

// ==================================================
// > SINGLES
// ==================================================
Route::is("single", "SingleController::render");

// ==================================================
// > SEARCH
// ==================================================
Route::is("search", "SpecialPageController::search", ["search" => get_search_query(false)]);

// ==================================================
// > API
// ==================================================
Route::custom("api", "ApiController", ["method", "target", "mode"]);

// ==================================================
// > NINJA FORM PREVIEW
// ==================================================
Route::query("nf_preview_form", "SpecialPageController::ninjaFormPreview");

// ==================================================
// > HOMEPAGE
// ==================================================
Route::is(["home", "front_page"], "HomeController::render");

// ==================================================
// > PAGES
// ==================================================
Route::is("page", "ContentPageController::render");

// ==================================================
// > 404
// ==================================================
Route::is("404", "SpecialPageController::error404");