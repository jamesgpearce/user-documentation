<?hh // strict

use HHVM\UserDocumentation\BuildPaths;
use HHVM\UserDocumentation\GuidesIndex;
use HHVM\UserDocumentation\HTMLFileRenderable;

use Psr\Http\Message\ServerRequestInterface;

final class GuidePageController extends WebPageController {
  protected string $guide;
  protected string $page;

  public function __construct(
    private ImmMap<string,string> $parameters,
    ServerRequestInterface $request,
  ) {
    parent::__construct($parameters, $request);
    $this->guide = $parameters->at('guide');
    $this->page = $parameters->at('page');
  }

  public async function getTitle(): Awaitable<string> {
    // If the guide name and the page name are the same, only print one of them.
    // If there is only one page in a guide, only print the guide name.
    $ret = strcasecmp($this->guide, $this->page) === 0 ||
           count(GuidesIndex::getPages($this->getProduct(), $this->guide)) === 1
         ? ucwords($this->guide)
         : ucwords(strtr($this->guide.': '.$this->page, '-', ' '));
    return $ret;
  }

  protected async function getBody(): Awaitable<XHPRoot> {
    return
      <div class="guidePageWrapper">
          {$this->getInnerContent()}
      </div>;
  }

  protected function getBreadcrumbs(): XHPRoot {
    $product = $this->getProduct();
    $guide = $this->guide;
    $product_root_url = sprintf(
      "/%s/",
      $product,
    );
    $guide_root_url = sprintf(
      "/%s/%s/",
      $product,
      $guide,
    );

    return
      <div class="breadcrumbNav">
        <div class="widthWrapper">
          <span class="breadcrumbRoot">
            <a href="/">Documentation</a>
          </span>
          <i class="breadcrumbSeparator" />
          <span class="breadcrumbProductRoot">
            <a href={$product_root_url}>{$product}</a>
          </span>
          <i class="breadcrumbSeparator" />
          <span class="breadcrumbSecondaryRoot">
            <a href={$product_root_url}>Learn</a>
          </span>
          <i class="breadcrumbSeparator" />
          <span class="breadcrumbCurrentPage">
            {ucwords(strtr($guide.': '.$this->page, '-', ' '))}
          </span>
        </div>
      </div>;
  }

  protected function getSideNav(): XHPRoot {
    $product = $this->getProduct();
    $guides = GuidesIndex::getGuides($product);

    $list = <ul class="navList" />;
    foreach ($guides as $guide) {
      $pages = GuidesIndex::getPages($product, $guide);
      $url = sprintf(
        "/%s/%s/%s",
        $product,
        $guide,
        $pages[0],
      );

      $title = ucwords(strtr($guide, '-', ' '));
      $sub_list = <ul class="subList" />;

      // If there is only one page to a guide, just have the main
      // guide heading above be the link to that page. Don't duplicate here.
      if (count($pages) > 1) {
        foreach ($pages as $page) {
          $page_url = sprintf(
            "/%s/%s/%s",
            $product,
            $guide,
            $page,
          );

          $page_title = ucwords(strtr($page, '-', ' '));
          $sub_list_item =
            <li class="subListItem">
              <h5><a href={$page_url}>{$page_title}</a></h5>
            </li>;

          if ($this->guide === $guide && $this->page === $page) {
            $sub_list_item->addClass("itemActive");
          }

          $sub_list->appendChild($sub_list_item);
        }
      }

      $list->appendChild(
        <li>
          <h4><a href={$url}>{$title}</a></h4>
          {$sub_list}
        </li>
      );
    }

    return
      <div class="navWrapper guideNav">
        {$list}
      </div>;
  }

  protected function getInnerContent(): XHPRoot {
    return self::invariantTo404(() ==> {
      $path = GuidesIndex::getFileForPage(
        $this->getRequiredStringParam('product'),
        $this->getRequiredStringParam('guide'),
        $this->getRequiredStringParam('page'),
      );
      return
        <div class="innerContent">{new HTMLFileRenderable($path)}</div>;
    });
  }

  <<__Memoize>>
  private function getProduct(): GuideProduct {
    return GuideProduct::assert(
      $this->getRequiredStringParam('product')
    );
  }
}
