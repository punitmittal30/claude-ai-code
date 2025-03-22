import { searchDataLayer } from "analytics/datalayer/search";
import { searchFbq } from "analytics/fbq/search";
import { searchTracker } from "analytics/trackers/search";
import useResize from "hooks/useResize";
import { setSeoContent } from "modules/Global/redux/reducer";
import { defaultFeature, featureObj } from 'modules/ProductDetail/constants';
import ShopByCategory from "modules/ProductListing/components/ShopByCategory";
import TopDeals from "modules/ProductListing/components/TopDeals";
import InfoListing from "modules/ProductListing/components/InfoListing";
import Image from "next/image";
import { useRouter } from "next/router";
import React, { useEffect, useMemo, useRef, useState } from "react";
import InfiniteScroll from 'react-infinite-scroll-component';
import ReactPaginate from "react-paginate";
import { useDispatch, useSelector } from "react-redux";
import Banners from "shared/components/Banners";
import Breadcrumbs from "shared/components/Breadcrumbs/Breadcrumbs";
import Loader from "shared/components/Loader/Loader";
import ProductSlider from "shared/components/ProductSlider";
import ListingSeo from "shared/components/Seo/ListingSeo";
import { catchErrors, replaceAll } from "utils/index";
import CategoryFilter from "./components/CategoryFilter";
import CategoryListing from "./components/CategoryListing";
import { allowedFilters, PaginationStyle, productInfo, sliderUrls, QuickFiltersData, inlineWidget } from "./components/constants";
import MobileFilter from "./components/mobile";
import {
  fetchCategoryDetails,
  fetchSearchDetails,
  setAppliedFilter,
  setEmptyFilter,
  setFacetsFromSearch,
  setFilterValues,
  setInitialLoad,
  setSelectedFilter,
  setShowHeaderOnOfferPage
} from "./redux/reducer";
import { trackSearch } from "analytics/plugins/webEngage/search";
import Infographics from "./components/Infographics";
import getConfig from 'next/config'
import axios from "axios";
import { searchSnaptr } from "analytics/plugins/snapchat/search";
import QuickFilters from "./components/mobile/components/QuickFilters";
const { publicRuntimeConfig } = getConfig();
import { requestContentInstance } from "service";
import { phTrackSearch } from "analytics/plugins/postHog/search";
import BubblesFilter from "./components/BubblesFilter";
import { useInView } from "react-intersection-observer";

const ProductListing = ({ isSearch, isBrand, isBenefits, isCategory, listingData, queryString, isMobile, bannerData }) => {
  const dispatch = useDispatch();
  const firstUpdate = useRef(true);
  const productLisingRef = useRef(null);
  const facetState = useSelector((state) => state.category);
  const banners = useSelector(state => state.home.data.banners)
  const getEddLocation = useSelector((state) => state.global.data.location_edd)
  const route = useRouter();
  const prevPathRef = useRef(null);
  const screen = useResize();
  const [slug, setSlug] = useState("");
  const [query, setQuery] = useState("");
  const [initLoad, setInitLoad] = useState(true)
  const [loading, setLoading] = useState(false);
  const [products, setProducts] = useState(isSearch ? (listingData?.data?.data?.products?.items ?? []) : listingData?.data?.products ?? []);
  const [filters, setFilters] = useState(isSearch ? (listingData?.data?.data?.products?.aggregations ?? []) : listingData?.data?.filters ?? []);
  const [subCategories, setSubCategories] = useState(listingData?.data?.subCategories ?? [])
  const [pageInfo, setPageInfo] = useState({ 
    title: listingData?.data?.category_data?.name ?? '',
    description: listingData?.data?.category_data?.description ? listingData?.data?.category_data?.description.replace('<p>', '').replace('</p>', '') : '',
    seo: listingData?.data?.category_data?.seo ?? ''
   });
  const [productCount, setProductCount] = useState(isSearch ? (listingData?.data?.data?.products?.total_count ?? 0) : listingData?.data?.total_count ?? 0);
  const [pageNo, setPageNo] = useState(1);
  const [initialPage, setInitalPage] = useState(0)
  const [breadcrumbs, setBreadcrumbs] = useState([]);
  const [inlineWidgetData, setInlineWidgetData] = useState([]);
  const [prevParam, setPrevParam] = useState({})
  const [itemOffset, setItemOffset] = useState(0);
  const [hasMore, setHasMore] = useState(true)
  const [currPath, setCurrpath] = useState('')
  const [filterParam, setFilterParam] = useState('')
  const getFreeShippingValue = useSelector((state) => state.global.data.free_shipping_config);
  const tickerLabel = getFreeShippingValue?.additional_label
  const itemsPerPage = 52;
  let scrollPos = 0;
  
  const {ref,inView} = useInView({rootMargin: '-300px 0px 0px 0px' ,threshold:0.1})
  const urls = useMemo(()=>{
    if(typeof(slug) === 'object' && slug.length !== 0) {
      if (slug.length > 1) return
      if(sliderUrls[slug[0]]) {
        return sliderUrls[slug[0]]
      } else {
        return {
          spotlight: `in-the-spotlight-${route.query.slug[0]}`,
          primary: `primary-carousel-${route.query.slug[0]}`,
          secondary: `secondary-carousel-${route.query.slug[0]}`,
          popularlyBought: `popularly-bought-${route.query.slug[0]}`
        } 
      }
    } else {
      return {}
    }
  },[slug])

  const createBreadcrumb = (path, page) => {
    if (path && path.length !== 0 && typeof path !== "string") {
      const breadcrumb = path.map((el, i) => {
        el = {
          id: i + 1,
          name: replaceAll(el, " "),
          href: `/product-category/${el}`,
        };
        if (path.length - 1 === i) {
          delete el["href"];
        }
        return el;
      });
      return [{ id: 0, name: "Home", href: "/" }, ...breadcrumb];
    } 
    else if (page) {
      if (typeof page !== "string" && page.href) {
        return [
          { id: 0, name: "Home", href: "/" },
          { id: 1, name: page?.name, href: page?.href },
          { id: 2, name: replaceAll(path, " ") },
        ];
      } else {
        return [
          { id: 0, name: "Home", href: "/" },
          { id: 1, name: page },
          { id: 2, name: replaceAll(path, " ") },
        ];
      }
    }
  };
  if (inlineWidgetData && inlineWidgetData.length > 1) inlineWidgetData.sort((a, b) => parseInt(a.priority) - parseInt(b.priority));
  const endOffset = itemOffset + itemsPerPage;
  const currentItems = products.slice(itemOffset, endOffset);
  const pageCount = Math.ceil(productCount / itemsPerPage);

  /**
   *
   * @param {*} facetObj
   * @param {*} res
   * function used to iterate the selected facets chip based from querystring
   */
  const createSelectedFacets = (facetObj, res, subCategories = []) => {
    if(JSON.stringify(facetObj) === '{}') {
      return;
    }
    const selectedArr = [];
    const cloneObj = JSON.parse(JSON.stringify(facetObj))
    if(cloneObj['category_ids']) {
      delete cloneObj['category_ids']
    }
    if(cloneObj['page']) {
      delete cloneObj['page']
    }
    const modifiedObj = Object.entries(cloneObj).map((facet) => {
      if (facet[0] === "ratings" || facet[0] === "price") {
        selectedArr.push({
          field: facet[0],
          label: `${facet[0]}: ${facet[0] === 'price' && '₹'}${facet[1].includes('_') ? facet[1].replace('_', '-₹'): facet[1]}`,
          value: facet[1],
        });
      }
      if(facet[0] === 'is_hl_verified') {
        selectedArr.push({
          field: facet[0],
          label: `H Tested`,
          value: facet[1][0],
        })
      }
      if(facet[0] === 'is_hm_verified') {
        selectedArr.push({
          field: facet[0],
          label: `H Metal Tested`,
          value: Number(facet[1][0]),
        })
      }
      return facet[1];
    });
    const flatten = modifiedObj.flat();
    for (let data of res) {
      if(data.attribute_code !== 'category_uid' &&
       data.attribute_code !== 'price' &&
        data.attribute_code !== 'is_hl_verified' &&
         data.attribute_code !== 'is_hm_verified') {
        const updatedFacets = data.options.forEach((option) => {
          if (flatten.includes(option.value)) {
            selectedArr.push({
              field: data.attribute_code,
              label: option.label,
              value: option.value,
            });
          }
        });
      }
    }
    if(facetObj['category_ids'] && facetObj['category_ids'].length !== 0) {
      const parseToNum = facetObj['category_ids'].map((val) => Number(val))
      for(let category of subCategories) {
        if (parseToNum.includes(Number(category.id))) {
          selectedArr.push({
            field: 'category_ids',
            label: category.name,
            value: Number(category.id),
          });
        }
      }
    }
    console.log('selectedarr is', selectedArr, facetObj)
    dispatch(setAppliedFilter(selectedArr));
  };

  // Invoke when user click to request another page.
  const handlePageClick = (event) => {
    const newOffset = (event.selected * itemsPerPage) % products.length;
    setItemOffset(newOffset);
    setPageNo(event.selected + 1);
    dispatch(setFilterValues({ field: "page", value: event.selected + 1 }));
   productLisingRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  /**
   *
   * @param {*} value
   * @returns querystring params for appending filters to the Api call
   */
  // const formatApiQuery = (value) => {
  //   if (!value) {
  //     setInitalPage(0)
  //     return "";
  //   }
  //   const apiQuery = value.split("&");
  //   const mapToQuery = apiQuery
  //     .filter((query) => {
  //       if (query.includes("q=") || (screen.device === 'mobile' && query.includes("page="))) {
  //         return false; 
  //       }
  //       return true;
  //     })
  //     .map((el) => {
  //       const splitStr = el.split("=");
  //       if(splitStr[0] === 'page') {
  //         setInitalPage(Number(splitStr[1]))
  //       }
  //       if (
  //         !splitStr[0].includes("price") &&
  //         !splitStr[0].includes("ratings") &&
  //         !splitStr[0].includes("sort_by") &&
  //         !splitStr[0].includes("page")
  //       ) {
  //         el = !splitStr[1] ? (el = "") : `${splitStr[0]}=[${splitStr[1]}]`;
  //       }
  //       return el;
  //     });
  //     const filterQuery = mapToQuery.filter((el) => {
  //       const querySplit = el.split('=')
  //       if(allowedFilters.includes(querySplit[0])) {
  //         return el
  //       }
  //     })
  //   return filterQuery.join("&");
  // };

  const convertToQueryDict = (filterObj) => {
    const obj = {};
    for (const facet in filterObj) {
      console.log('facet is', facet, filterObj[facet])
      if(facet !== 'q') {
        const encodingUri = encodeURIComponent(filterObj[facet]?.toString())
        obj[facet] = decodeURIComponent(encodingUri);
      }
    }
    return obj;
  };

  const facetFields = (search) => {
    if (!search) {
      return {};
    }
    const filterObj = {};
    const modifySearch = search.split("&");
    for (const param of modifySearch) {
      const urlSplit = param.split("=");
      if(urlSplit[0] !== "q") {
        if (
          urlSplit[0] !== "ratings" &&
          urlSplit[0] !== "price" &&
          urlSplit[0] !== "sort_by" &&
          urlSplit[0] !== "page"
        ) {
          filterObj[urlSplit[0]] = urlSplit[1]?.split(",");
        } else {
          filterObj[urlSplit[0]] = urlSplit[1];
        }
      }
    }
    if(filterObj && filterObj['category_ids']) {
      filterObj['category_ids'] = filterObj['category_ids'].map((x) => Number(x))
    }
    console.log('filterobj is', filterObj)
    return filterObj;
  };

  const compareSlug = (slug) => {
    if (slug) {
      const path = location.pathname.replace("/", "").split("/");
      const getUrl = path[path.length - 1];
      let modifiedSlug;
      if (typeof slug === "object") {
        modifiedSlug = slug[slug.length - 1];
      } else {
        modifiedSlug = slug;
      }
      if (modifiedSlug === getUrl) {
        return false
      }
      console.log('came here to initial Load another page')
      dispatch(setInitialLoad(true));
      dispatch(setEmptyFilter());
      setSubCategories([])
      dispatch(setAppliedFilter([]));
      if(screen.device === 'mobile') {
        setHasMore(true)
        setPageNo(1)
        setFilterParam(null)
      }
      return true
    }
  };

  const compareSearch = (search) => {
    if(search !== route.query.q) {
      dispatch(setInitialLoad(true));
      dispatch(setEmptyFilter());
      setSubCategories([])
      dispatch(setAppliedFilter([]));
      if(screen.device === 'mobile') {
        setHasMore(true)
        setPageNo(1)
        setFilterParam(null)
      }
    }
  }

  const initialUpdate = () => {
    if (JSON.stringify(facetState.filters) === "{}" && location.search !== "") {
      console.log('initial update is', facetState.filters, location.search)
      const existingParams = new URLSearchParams(location.search);
      if (isSearch) {
        setQuery(existingParams.get("q"));
      }
      const urlSearch = decodeURIComponent(location.search)
        .toString()
        .replace("?", "");
      dispatch(setFacetsFromSearch(facetFields(urlSearch)));
      route.push({
        pathname: location.pathname,
        query: isSearch ? { q: new URLSearchParams(location.search).get("q"), ...Object.fromEntries(existingParams) } : Object.fromEntries(existingParams),
      });
    }
  };

  const handleScroll = (e) => {
    let top = 0;
    let scrollupTop = 0;
    const filterWrapper = document.querySelector(".category-filter-container");
    let innerHeight = window.innerHeight;
    let scroll = window.pageYOffset;
    if (scroll > scrollPos) {
      let height = filterWrapper?.clientHeight;
      top = height - innerHeight;
      scrollupTop = top;
      if (filterWrapper) {
        filterWrapper.style.top = `-${top}px`;
      }
    }
  };

  /**
   * Any route, queryparam changes inside PLP will trigger this hook
   */
  useEffect(() => {
    const currentPath = route.asPath;
    if (prevPathRef.current !== null && currentPath === prevPathRef.current) {
      if(getEddLocation) {
        if(isSearch) {
          fetchProductCategorySsr(queryString);
        } else {
          const urlSearch = decodeURIComponent(location.search).toString().replace("?", "");
          fetchProductCategorySsr(queryString, null, null, urlSearch);
        }
      }
      return;
    }
    prevPathRef.current = currentPath;

    setInitLoad(false)
    let searchParam = new URLSearchParams(location.search)
    if(searchParam?.has('page')) {
      setInitalPage(Number(searchParam.get('page')) ?? 1)
    } else {
      setInitalPage(1)
    }
    if(isSearch) {
      compareSearch(query)
    } else {
      compareSlug(slug);
    }
    route.beforePopState((state) => {
      if(['/men', '/women', '/'].includes(state.as)) {
        state.options.scroll = false
        return true;
      }
      state.options.scroll = false
      console.log('called pressed back button', state, state.options)
      if(isSearch) {
        const urlSearch = decodeURIComponent(location.search)
        .toString()
        .replace("?", "");
      dispatch(setFacetsFromSearch(facetFields(urlSearch)));
      } else {
        setSlug("");
      }
      return true;
    });
    if (route.query.slug) {
      if(route.asPath === currPath) {
        return;
      }
      setSlug(route.query.slug);
      if (isBrand || isBenefits) {
        setBreadcrumbs(createBreadcrumb(route.query.slug,(isBrand ? {name: "brands", href: `/brands`}: "benefits")));
      } else {
        setBreadcrumbs(createBreadcrumb(route.query.slug));
      }
      const urlSearch = decodeURIComponent(location.search)
        .toString()
        .replace("?", "");
      setCurrpath(route.asPath)
      dispatch(setFacetsFromSearch(facetFields(urlSearch)));
      fetchProductCategorySsr(queryString, null, null, urlSearch);
    }

    if (route.query.q && isSearch) {
      setQuery(route.query.q);
      if (isSearch) {
        setBreadcrumbs(createBreadcrumb(route.query.q, "search"));
      }
        fetchProductCategorySsr(queryString);
    } else if(isSearch && !route.query.q && !searchParam.get('q')) {
      dispatch(setInitialLoad(false))
    }
    return () => {
      route.beforePopState(() => true);
    };
  }, [route]);


  const fetchInlineWidgetData = async (slug) => {
    setInlineWidgetData([])
    try {
      setLoading(true);
      if (slug) {
        const data = {
          method: "get",
          url: `/content/banners/${slug}/plp-inline`,
        };
        const resp = await requestContentInstance(data);
        if(!resp?.data?.status) {
          setLoading(false)
          setInlineWidgetData([])
          return
        }
        if (resp?.data?.data) {
          if (Array.isArray(resp.data.data)) {
            setInlineWidgetData(resp.data.data)
          } else {
            setInlineWidgetData([resp.data.data]);
          }
        };
      }
    } catch (error) {
      setLoading(false)
      setInlineWidgetData([])
      catchErrors(error);
    } finally {
      setLoading(false);
    };
  };

  useEffect(() => {
    if (route.query.slug) {
      fetchInlineWidgetData((isSearch)
        ? null
        : (isBrand || isBenefits)
          ? route.query.slug
          : route.query.slug[route.query.slug.length - 1]
      );
    }
  }, [route.query.slug]);

  /**
   * Detect scroll event for sticky filter
   */
  useEffect(() => {
    window.addEventListener("scroll", handleScroll);
    return () => {
      setSlug("");
      setPageNo(1)
      setHasMore(true)
      setProducts([])
      setPageInfo({})
      setFilters([])
      setFilterParam(null)
      setProductCount(0)
      dispatch(setSeoContent(null))
      dispatch(setInitialLoad(true));
      dispatch(setEmptyFilter());
      dispatch(setAppliedFilter([]))
      window.removeEventListener("scroll", handleScroll);
    };
  }, []);

  /**
   * Any filter to be applied will update the queryparam and route
   */
  useEffect(() => {
    if (firstUpdate.current) {
      firstUpdate.current = false;
      initialUpdate();
      return;
    }
    const filters = JSON.parse(JSON.stringify(facetState.filters));
    if(JSON.stringify(prevParam) !== JSON.stringify(filters)) {
      route.push(
        { pathname: location.pathname, query: isSearch ? { q: new URLSearchParams(location.search).get("q"), ...convertToQueryDict(filters) } : convertToQueryDict(filters) },
        undefined,
        { scroll: false }
      );
      setPrevParam(filters)
    }
  }, [JSON.stringify(facetState.filters)]);

    //  set scroll restoration to manual
    useEffect(() => {
      if ('scrollRestoration' in history && history.scrollRestoration !== 'manual') {
        history.scrollRestoration = 'manual';
      }
    }, []);
  
    // handle and store scroll position
    useEffect(() => {
      const handleRouteChange = () => {
        sessionStorage.setItem('scrollPosition', window.scrollY.toString());
      };
      route.events.on('routeChangeStart', handleRouteChange);
      return () => {
        route.events.off('routeChangeStart', handleRouteChange);
      };
    }, [route.events]);
  
    // restore scroll position
    useEffect(() => {
      if ('scrollPosition' in sessionStorage) {
        setTimeout(() => {
          window.scrollTo(0, Number(sessionStorage.getItem('scrollPosition')));
          sessionStorage.removeItem('scrollPosition');
        }, 0);
  
      }
    }, []);

  const fetchProductCategorySsr = async (queryString, isInfinite, pageNo, locationSearch = '', isClient = false) => {
    try {
      if(!isInfinite)
        setLoading(true);
      let endPointParam;
      if(isClient) {
          if (isSearch) {
            endPointParam = `/search?query=${new URLSearchParams(
              location.search
            ).get("q")}&page_size=${itemsPerPage}`;
          } else {
            endPointParam = `/search/filters?category_slug=${(isBrand || isBenefits)
                ? route.query.slug
                : route.query.slug[route.query.slug.length - 1]
              }&page_size=${itemsPerPage}`;
          }
      }
        if (queryString) {
          if(!filterParam) {
            setFilterParam(queryString)
          }
          if(!queryString.includes('page=')) {
            setInitalPage(0)
          }
          if(filterParam !== queryString) {
            setPageNo(1)
            setFilterParam(queryString)
            setHasMore(true)
          }
          endPointParam += "&" + queryString;
        }

      if(isInfinite && pageNo && isClient) {
        endPointParam += `&page=${pageNo + 1}`
      }
      let categoryList = listingData
      if(isClient) {
        const data = {
          method: "get",
          url: endPointParam,
        };
        let resp;
        if (isSearch) {
          resp = await dispatch(fetchSearchDetails(data)).unwrap();
        } else {
          resp = await dispatch(fetchCategoryDetails(data)).unwrap();
        }
        categoryList = resp?.data;
      }
      console.debug('PLP response: ', categoryList)
      if (!categoryList?.status) {
        setLoading(false);
        dispatch(setInitialLoad(false));
        setProducts([])
        setFilters([])
        setSubCategories([])
        setProductCount(0)
        setPageInfo({})
        return catchErrors(null, categoryList?.data?.message);
      }
      if ((isBrand || isBenefits) && categoryList?.data?.products?.length === 0) {
        setLoading(false)
        dispatch(setInitialLoad(false));
        setProducts([])
        setSubCategories([])
        setProductCount(0)
        setPageInfo({})
        return;
      }
      if (isSearch) {
        if(categoryList.data.data.products.items.length >= categoryList.data.data.products.total_count) {
          setHasMore(false)
        } else {
          setHasMore(true)
        }
        if(isInfinite) {
          setProducts([...products, ...categoryList.data.data.products.items])
        } else {
          if(!facetState.loading && !isClient) {
            setFilters(categoryList.data.data.products.aggregations)
            setProducts(categoryList.data.data.products.items)
          }
        }
        setSubCategories([])
        setProductCount(categoryList.data.data.products.total_count);
        setProductCount(categoryList.data.data.products.total_count);
        createSelectedFacets(
          facetState.filters,
          categoryList.data.data.products.aggregations
        );
        searchDataLayer(route.query.q);
        searchTracker({
          search: {
            hits: categoryList?.data?.data?.products?.total_count,
            query: route.query.q
          }
        })
        searchFbq(route.query.q, categoryList?.data?.data?.products?.total_count);
        trackSearch({
          search: {
            hits: categoryList?.data?.data?.products?.total_count,
            query: route.query.q
          }
        });
        searchSnaptr({
          search: {
            hits: categoryList?.data?.data?.products?.total_count,
            query: route.query.q
          },
        });   
        phTrackSearch({
          search: {
            hits: categoryList?.data?.data?.products?.total_count,
            query: route.query.q
          },
        });
        setTimeout(() => {
          dispatch(setInitialLoad(false));
        }, 300)
      } else {
        if(categoryList?.data?.products?.length >= categoryList?.data?.total_count) {
          setHasMore(false)
        } else {
          setHasMore(true)
        }
        if(isInfinite) {
          setProducts([...products, ...categoryList.data.products]);
        } else {
          if(!facetState.loading && !isClient) {
            setFilters(categoryList?.data?.filters)
            setProducts(categoryList?.data?.products)
          }
        }
        createSelectedFacets(facetFields(locationSearch), categoryList.data.filters, (categoryList?.data?.subCategories || []));
        setProductCount(categoryList.data.total_count);
        setSubCategories(categoryList?.data?.subCategories ?? [])
        setPageInfo({
          title: categoryList?.data?.category_data?.name ?? '',
          description: categoryList?.data?.category_data?.description ?? '',
          seo: categoryList?.data?.category_data?.seo ?? ''
        });
        dispatch(setSeoContent(categoryList?.data?.category_data?.seo?.seo_content ?? null))
        dispatch(setInitialLoad(false));
      }
      setLoading(false)
    } catch (e) {
      setLoading(false)
      setProducts([])
      dispatch(setInitialLoad(false))
      setFilters([])
      setSubCategories([])
      setProductCount(0)
      setPageInfo({})
      console.log('error', e)
    } finally {
      setLoading(false);
    }
  };

  const fetchNextData = () => {
    if(products.length >= productCount) {
      setHasMore(false)
      return;
    }
    setPageNo(pageNo + 1);
    const urlSearch = decodeURIComponent(location.search).toString().replace("?", "");
    fetchProductCategorySsr(queryString, true, pageNo, urlSearch, true);
  }
  

    useEffect(()=>{
     dispatch(setShowHeaderOnOfferPage(inView))
    },[inView])
 
  return (
    <>
    {facetState.loading && <div className="bg-white z-[1] h-full relative sm:hidden">
      <Loader  styleClass={'white-background-loader'}/>
    </div>}  
        <div className={`product-listing-container h-full bg-white pr-0`}>
          {(!isSearch && !isBenefits && !route?.asPath?.includes('/product-category/offer-zone')) ? <Banners isListing isMobile={isMobile} bannerData={bannerData} slug={(isBrand || isBenefits)
            ? route.query.slug
            : route.query.slug[route.query.slug.length - 1]} identifier={ isBrand ? 'brand-hero-banner' : 'category-hero-banner'} />: null}
          <div className="md:block sm:py-6 sm:pt-4">
            <Breadcrumbs breadcrumbs={breadcrumbs} />
            <ListingSeo
              page={{
                title: isSearch ? `${query}` : pageInfo.title,
                description: pageInfo.description,
                seo: pageInfo.seo
              }}
              canonical={
                isSearch ? `/search?q=${encodeURIComponent(route.query.q)}` :
                isBrand ? route.asPath :
                isBenefits ? route.asPath : 
                isCategory ? route.asPath: ''
              }
              breadcrumbs={breadcrumbs}
              isSearch = {isSearch}
              productList = {currentItems}
            />
          </div>
          {loading ? <Loader styleClass={"backdrop-blur-[1px] z-10 "} /> : null}
          <div className="product-listing-main">
                {(urls && Object.keys(urls).length) && !route?.asPath?.includes('/product-category/offer-zone') ?
                  <ProductSlider
                    categoryId={78}
                    horizontalLine={true}
                    categorySlug={urls.spotlight}
                    featureSource={'category-in-the-spotlight'}
                  /> : null}
            {!isSearch && !route?.asPath?.includes('/product-category/offer-zone') ? <div className="mx-auto max-w-7xl lg:px-7 infpgraphics-slider">
              <Infographics isBrand={isBrand} isBenefits={isBenefits}  />
            </div>: null}

           {!route?.asPath?.includes('/product-category/offer-zone') ? <div className="mx-auto max-w-7xl lg:px-7">
              {
                (urls && Object.keys(urls).length) ?
                    <>
                      <ProductSlider
                          categoryId={78}
                          noPadding
                          horizontalLine={true}
                          categorySlug={urls.primary}
                          featureSource={'category-in-the-spotlight'}
                      />

                    <div className="sm:mb-8 pb-0">
                       <TopDeals isMobile={screen.device === 'mobile'} horizontalLine={true} slug={slug[0]}/>
                     </div>

                      <ProductSlider
                         categoryId={78}
                         noPadding
                         horizontalLine={true}
                         categorySlug={urls.secondary}
                         featureSource={'category-popular-bought'}
                     />
                     <ProductSlider
                         categoryId={78}
                         noPadding
                         horizontalLine={true}
                         categorySlug={urls.popularlyBought}
                         featureSource={'category-popular-bought'}
                     />
                    </> : ''
              }
            </div>:''}

            {
              (urls && Object.keys(urls).length) && !route?.asPath?.includes('/product-category/offer-zone') ? <ShopByCategory slug={slug[0]} position={1} /> : ''
            }

            <div ref={productLisingRef} className="product-category-cards mx-auto max-w-7xl lg:px-7">
            {/* <div className="w-full bg-[#F4F4F9] h-2 flex sm:hidden" />  */}

            {route?.asPath?.includes('/product-category/offer-zone') && <BubblesFilter filters={facetState.filters}/>}
              <div className="category-listings">
              {
                screen.device === "mobile" && inlineWidgetData.length && inlineWidgetData[0]?.priority == "0"
                  ?
                  inlineWidget(inlineWidgetData[0]?.template, inlineWidgetData[0])
                  : ""
              }
                <p className="text-lg px-4 sm:hidden font-semibold pt-4 pb-1 text-gray-100" >All products</p>
                {productCount > 0 ? <div className={`${isSearch ? 'pt-[18px]' : ''} text-gray-50 text-sm sm:text-base px-4 lg:pl-0 pb-4 sm:py-3 sm:mb-6 sm:bg-white flex`} id="category-filter" ref={ref}>
                  Showing
                  <span className="text-gray-100 font-semibold px-1">
                    {productCount}
                  </span>
                    results of <h1 className="text-gray-100 font-semibold px-1 capitalize"> {isSearch ? route.query.q : (breadcrumbs && breadcrumbs.length) ? breadcrumbs[breadcrumbs.length - 1]?.name : ""}</h1>
                </div>: null}
                        
                {
                  screen.device === "mobile" && Array.isArray(route.query.slug) && !route?.asPath?.includes('/product-category/offer-zone') ?
                    <div className={`sticky ${tickerLabel ? 'top-[90px]':'top-[58px]'} py-2 bg-white z-[2]`}>
                      <QuickFilters
                        quickFilterData={QuickFiltersData}
                        categorySlug={route.query}
                        selectedFilter={facetState.filters}
                        selectedChips={facetState.selectedFilter}
                        filters={filters}
                      />
                    </div> : null
                }

                <div className={`flex mt-3 ${products.length === 0 ? 'sm:mt-20':''}`} >
                  {(screen.device !== "mobile" && filters?.length !== 0) ? (
                    <div className="relative hidden sm:inline-flex flex-col md:basis-2/4 min-w-[250px] xl:w-full max-w-[280px] w-[250px] lg:max-w-[320px] xl:max-w-[280px] px-4 lg:pl-0 pr-2 md:pr-4">
                      <CategoryFilter
                        filters={filters}
                        isBrand={isBrand}
                        selectedFilter={facetState.filters}
                        subCategories={subCategories}
                        categoryTitle={pageInfo?.title}
                      />
                    </div>
                  ) : null}

                  <div className="basis-full relative">
                    {(screen.device === 'mobile' || isMobile) ? <InfiniteScroll
                    dataLength={products.length}
                    next={fetchNextData}
                    hasMore={hasMore}
                    scrollThreshold={0.7}
                    loader={(products && products.length > 0) ? <div className="scroll-loader">
                      <Loader staticMode={true} />
                      </div>: null}
                    >
                    <CategoryListing
                      loading={loading}
                      isSearch={isSearch}
                      singleLayout={true}
                      device={screen.device}
                      ssrDevice={isMobile}
                      products={products}
                      filterState={facetState.filters}
                      selectedFilter={facetState.selectedFilter}
                      listingSource={
                        isSearch ? 'search' :
                        isBrand ? 'brand' :
                        isBenefits ? 'benefits' : 
                        isCategory ? 'category' : 
                        'product-listing'
                      }
                      inlineWidgetData={inlineWidgetData}
                    />
                      </InfiniteScroll> :
                    <CategoryListing
                      loading={loading}
                      isSearch={isSearch}
                      device={screen.device}
                      products={currentItems}
                      ssrDevice={isMobile}
                      filterState={facetState.filters}
                      selectedFilter={facetState.selectedFilter}
                      listingSource={
                        isSearch ? 'search' :
                        isBrand ? 'brand' :
                        isBenefits ? 'benefits' : 
                        isCategory ? 'category' : 
                        'product-listing'
                      }
                      inlineWidgetData={inlineWidgetData}
                    />}
                    {(screen.device !== 'mobile' && productCount > itemsPerPage) ? <ReactPaginate
                      breakLabel="..."
                      nextLabel={<>&raquo;</>}
                      onPageChange={handlePageClick}
                      pageRangeDisplayed={3}
                      forcePage={initialPage !== 0 ? initialPage - 1 : initialPage} 
                      pageCount={pageCount}
                      marginPagesDisplayed={2}
                      pageLinkClassName={`${PaginationStyle}`}
                      breakLinkClassName={PaginationStyle}
                      nextLinkClassName={`${PaginationStyle} rounded-r-md ${initialPage === pageCount  ? 'hidden' : 'block'}`}
                      previousLinkClassName={`${PaginationStyle} rounded-l-md ${initialPage === 0 ? 'hidden' : 'block'}`}
                      activeClassName={
                        `text-white bg-hyugapurple-500 border-[bg-hyugapurple-500] w-[36px]`
                      }
                      containerClassName={
                        "flex list-none justify-center my-6 absolute -translate-y-2/4 -translate-x-2/4 left-[50%] bottom-[-25px]"
                      }
                      previousLabel={<>&laquo;</>}
                      renderOnZeroPageCount={null}
                    />: null}
                  </div>
                </div>
                {screen.device === "mobile" ? (
                  <MobileFilter
                    selectedFilter={facetState.filters}
                    selectedChips={facetState.selectedFilter}
                    filters={filters}
                    isBrand={isBrand}
                    isSearch={isSearch}
                    productCount={productCount}
                    subCategories={subCategories}
                    categoryTitle={pageInfo?.title}
                  />
                ) : (
                  ""
                )}
              </div>
            </div>
            <div className="block mx-auto max-w-7xl lg:px-7">
              {(pageInfo?.title && pageInfo?.description && !initLoad) ? (
                <div className={`category-title ${banners?.length === 0 ? 'pt-4' : ''} px-4 mb-4 sm:mb-8 sm:p-6 rounded-lg sm:bg-[#EDF5FF]`}>
                  <span
                    className="font-semibold text-base sm:text-lg text-gray-100 pb-1 sm:pb-3"
                  >{pageInfo?.title}</span>
                  {pageInfo?.description && <p
                    className="text-gray-50 text-base sm:text-base"
                    dangerouslySetInnerHTML={{ __html: pageInfo?.description ?? ''}}
                  ></p> }
                </div>
              ) : null}
            </div>
            {!isSearch && <>
              {!isCategory && <div className="w-full bg-[#F4F4F9] h-2 flex sm:hidden" />}
              <InfoListing isDefault />
              <div className="w-full bg-[#F4F4F9] h-2 flex sm:hidden" /> </>}

          </div>
          
        </div>
    </>
  );
};

export default ProductListing;