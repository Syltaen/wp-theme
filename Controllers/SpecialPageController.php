<?php

namespace Syltaen;

class SpecialPageController extends PageController
{


    /**
     * Error 404 page display
     *
     * @return output HTML
     */
    public function error404()
    {
        global $pagename;

        Data::store($this->data, [
            "@lookfor" => $pagename
        ]);

        // Make sure the error404 is set on the body
        $this->addBodyClass("page404");

        // Remove the breadcrumb
        // $this->data["site"]["breadcrumb"] = "";

        // Make sure the correcet header is set
        status_header("404");
        $this->render("404");
    }

    /**
     * Display a form
     *
     * @param int $form_id The ID of the form to display
     * @return void
     */
    public function ninjaFormPreview()
    {
        Data::store($this->data, [
            "@sections"      => [[
                "classes" => "",
                "attr"    => "",
                "content" => [[
                    "acf_fc_layout" => "txt_1col",
                    "txt"           => "[ninja_form id=".$this->args[0]."]"
                ]]
            ]],
        ]);

        $this->render();
    }

    /**
     * Search results page
     *
     * @param string $search Terms to search for
     * @return output HTML
     */
    public function search($search = false)
    {
        $search = $search ?: $this->args["search"];

        $models_to_search = [
            new Pages
        ];

        $this->data["results"] = [];
        $total_results_count   = 0;

        foreach ($models_to_search as $model) {
            $this->data["results"][$model::TYPE] = [
                "posts" => $model->search($search)->get(),
                "count" => $model->count(),
                "label" => $model::LABEL
            ];
            $total_results_count += $model->count();
        }

        $total_results_count = $total_results_count > 1 ? $total_results_count." résultats" : ($total_results_count < 1 ? "Pas de résultat" : "Un seul résultat");

        Data::store($this->data, [
            "@title"       => __("Recherche pour : ", "syltaen")." <span class='search-page__title__words'>$search</span><br><small>$total_results_count</small>",
            "@search"      => $search
        ]);

        $this->addBodyClass("search-page");
        $this->render("search");
    }
}