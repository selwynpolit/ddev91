<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* core/themes/bartik/templates/classy/field/field--node--title.html.twig */
class __TwigTemplate_93e6e34b3069d0542b5022736d462c82774886bebf46c07ab10e095c794a6244 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $__internal_b8a44bb7188f10fa054f3681425c559c29de95cd0490f5c67a67412aafc0f453 = $this->extensions["Drupal\\webprofiler\\Twig\\Extension\\ProfilerExtension"];
        $__internal_b8a44bb7188f10fa054f3681425c559c29de95cd0490f5c67a67412aafc0f453->enter($__internal_b8a44bb7188f10fa054f3681425c559c29de95cd0490f5c67a67412aafc0f453_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "core/themes/bartik/templates/classy/field/field--node--title.html.twig"));

        // line 28
        if ( !($context["is_inline"] ?? null)) {
            // line 29
            echo "  ";
            $this->loadTemplate("field.html.twig", "core/themes/bartik/templates/classy/field/field--node--title.html.twig", 29)->display($context);
        } else {
            // line 32
            $context["classes"] = [0 => "field", 1 => ("field--name-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed(            // line 34
($context["field_name"] ?? null), 34, $this->source))), 2 => ("field--type-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed(            // line 35
($context["field_type"] ?? null), 35, $this->source))), 3 => ("field--label-" . $this->sandbox->ensureToStringAllowed(            // line 36
($context["label_display"] ?? null), 36, $this->source))];
            // line 39
            echo "<span";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [0 => ($context["classes"] ?? null)], "method", false, false, true, 39), 39, $this->source), "html", null, true);
            echo ">";
            // line 40
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["items"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 41
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["item"], "content", [], "any", false, false, true, 41), 41, $this->source), "html", null, true);
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 43
            echo "</span>
";
        }
        
        $__internal_b8a44bb7188f10fa054f3681425c559c29de95cd0490f5c67a67412aafc0f453->leave($__internal_b8a44bb7188f10fa054f3681425c559c29de95cd0490f5c67a67412aafc0f453_prof);

    }

    public function getTemplateName()
    {
        return "core/themes/bartik/templates/classy/field/field--node--title.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  67 => 43,  61 => 41,  57 => 40,  53 => 39,  51 => 36,  50 => 35,  49 => 34,  48 => 32,  44 => 29,  42 => 28,);
    }

    public function getSourceContext()
    {
        return new Source("", "core/themes/bartik/templates/classy/field/field--node--title.html.twig", "/var/www/html/web/core/themes/bartik/templates/classy/field/field--node--title.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 28, "include" => 29, "set" => 32, "for" => 40);
        static $filters = array("clean_class" => 34, "escape" => 39);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'include', 'set', 'for'],
                ['clean_class', 'escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
