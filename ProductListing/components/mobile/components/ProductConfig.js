import { ChevronRightIcon, XIcon } from "@heroicons/react/outline";
import { StarIcon } from "@heroicons/react/solid";
import { addToCartDataLayer, productCardClickDataLayer } from "analytics/datalayer/cart";
import { addToCartFbq } from "analytics/fbq/cart";
import { addToCartSnaptr } from "analytics/plugins/snapchat/cart";
import { trackAddToCart , trackProductCardClick} from "analytics/plugins/webEngage/cart";
import { addToCartTracker } from "analytics/trackers/cart";
import { getCookie } from "cookies-next";
import useNotify from "hooks/useNotify";
import useUpdateCart, { createNewCartId } from "hooks/useUpdateCart";
import {addToCart, setShowAnimation, setShowCart, removeToCart} from "modules/Cart/redux/reducer";
import {
  setNotifyProduct,
  setProductOption,
  setFrequentlyBought
} from "modules/ProductListing/redux/reducer";
import getConfig from "next/config";
import { useRouter } from "next/router";
import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import VariantSelection from "shared/components/VariantSelection/VariantSelection";
import { calculateHoursDifference, catchErrors, compressImage, getDeliveryHours, getDeliveryTime } from "utils/index";
const { publicRuntimeConfig } = getConfig();
import { v4 as uuidv4 } from 'uuid';
import { getIsoTime, getSelectedFilter } from "analytics/helpers";
import useResize from "hooks/useResize";
import Slider from "@ant-design/react-slick";
import { variantSliderSettings } from "shared/components/Banners/constants";
import { getServingsAttr } from "shared/components/ProductCard/constants";
import Counter from "modules/Cart/components/Counter";
import { requestCatalogInstance } from "service";
import {
  updateCart,
 
} from "modules/Cart/redux/reducer";
import Loader from "shared/components/Loader/Loader";
import FrequentlyBought from "shared/components/Sidebar/FrequentlyBought";
import { getCartState} from "modules/Cart/redux/reducer"

import { phAddToCart, phProductCardClick } from "analytics/plugins/postHog/cart";
import { setDrawer,setDialog } from "modules/Dialog/redux/reducer";
import { productIncrementDataLayer, productDecrementDataLayer } from "analytics/datalayer/cart";
import { productIncrementTracker, productDecrementTracker } from "analytics/trackers/cart";
import { trackProductIncrement, trackProductDecrement } from "analytics/plugins/webEngage/cart";
import { productIncrementFbq, productDecrementFbq} from "analytics/fbq/cart";
import { phProductIncrement, phProductDecrement } from "analytics/plugins/postHog/cart";
import { removeFromCartFbq } from "analytics/fbq/cart";
import { removeFromCartDataLayer } from "analytics/datalayer/cart";
import { removeFromCartTracker } from "analytics/trackers/cart";
import { trackRemoveFromCart } from "analytics/plugins/webEngage/cart";
import DeliveryBadge from "../../DeliveryBadge";
import { useFeatureFlagVariantKey } from 'posthog-js/react'

const ProdConfig = ({drawerState, paddingAdjust, closeSideBar}) => {
  const dispatch = useDispatch();
  const { isMobile } = useResize()
  const router = useRouter()
  const isLoggedIn = getCookie("isLoggedIn");
  const type = isLoggedIn ? "customer" : "guest";
  const user = getCookie("user") ? JSON.parse(getCookie("user")) : null;
  const product = useSelector((state) => state.category.variants);
  const featureSource = useSelector((state) => state.category.featureSource);
  const featureValue = useSelector((state) => state.category.featureValue);
  const featurePosition = useSelector((state) => state.category.featurePosition);
  const selectedFilters = useSelector((state) => state.category.selectedFilters);
  const featureClass =  useSelector((state) => state.category.featureClass);
  const cartState = useSelector(getCartState);
  const cartItems = useSelector((state) => state.cart.data.items);
  const [notAvailable, setNotAvailable] = useState(false);
  const [activeOption, setActiveOption] = useState(null);
  const [optionIndex, setOptionIndex] = useState(0)
  const [showCounter,setShowCounter]=useState(false)
  const [showFrequentBought,setShowFrequentBought]=useState(false)
  const [frequentBoughtProd,setFrequentBoughtProd]= useState([])
  const [quantity,setQuantity]=useState(product.min_sale_qty || 1)
  const [isInitQtyAdded ,setIsInitQtyAdded] = useState(false)
  const [estimatedTime, setEstimatedTime] = useState(null)
  const [loading,setLoading]=useState(false)
  const route = useRouter();
  const totalRef = useRef('')
  const cardOnListing = ['/product-category/[...slug]','/search'].includes(router.pathname)

  const getCartQty = (product) => {
    const getExactProduct = cartItems.find((item) => item.product_id == product.id)

    if(getExactProduct?.product_id) {
    
            return { currProduct:  getExactProduct, qty : quantity }
    } else {
      return {currProduct: product, qty: quantity ||  Number(product?.stock_info?.min_sale_qty)}
    }
  }

  const prepareData = (parseCart, product) => {
    const { currProduct, qty } = getCartQty(product)
    if(currProduct.qty === product?.stock_info?.max_sale_qty) {
      useNotify('success', 'notify-toast', null, null, `Maximum ${product?.stock_info?.max_sale_qty} quantities can be added for this item`)
      return;
    }
    const type = isLoggedIn ? "customer" : "guest";
    const url = `/cart/${type}/add/${parseCart.quoteId}`;
    const productData = {
      quote_id: parseCart.quoteId,
      sku: product.sku,
      qty: qty,
    };

    if (activeOption) {
      productData.sku = activeOption.sku || activeOption.product.sku;

      productData.product_option = {
        extension_attributes: {
          configurable_item_options: [
            {
              option_id: activeOption.option_id || activeOption.product.id,
              option_value:
                activeOption.value_index ||
                activeOption.attributes[0].value_index,
            },
          ],
        },
      };
    }

    return {
      method: "post",
      url: url,
      data: { cartItem: productData },
    };
  };

  const getDefaultVariant = (product) => {
    if (product && product?.default_variant_id) {
        if (product?.url_key) {
            const getProduct = product?.variants?.filter((el, i) => Number(el.product.id) === Number(product?.default_variant_id))[0]
            if (getProduct?.product?.url_key) {
                return getProduct || {}
            }
        } else {
            const getProduct = product?.configurable_product_options[0].values.variants.filter((el, i) => Number(el.product_id) === Number(product?.default_variant_id))[0]
            if (getProduct?.slug) {
                return getProduct || {}
            }
        }
    } else {
        if (product?.variants && product?.variants?.length !== 0) {
            return product.variants[0] || {}
        } else if(product?.configurable_product_options && product?.configurable_product_options.length !== 0) {
            return product?.configurable_product_options[0]?.values.variants[0] || {}
        } else {
            return {}
        }
    }
}

  useEffect(() => {
    if (product) {
      setActiveOption(getDefaultVariant(product));
      getEstimationTime(getDefaultVariant(product)?.estimated_delivery_time?.delivery_time ?? getDefaultVariant(product)?.product?.estimated_delivery_time?.delivery_time)
    }
  }, []);


  useEffect(() => { 
    if(drawerState) {
      document.body.classList.add("active-variant-drawer");
      document.documentElement.style.overflow = "hidden"; 
    }
  }, [drawerState])

  const onProductCardClick = (product) => {
    try {
      const extras = {
        featureSource: featureSource,
        featureValue: featureValue,
        featurePosition: featurePosition,
        deals: product.deal_of_the_day ? "Deal of the day" :
          product.special_to_date_formatted ? "Flash deal " : null,
        timeDeal: product.special_to_date_formatted ? getIsoTime(product.special_to_date_formatted) : null,
        ...getSelectedFilter(selectedFilters),
      };
      trackProductCardClick({ product, extras });
      productCardClickDataLayer(product, featureSource, featureValue, featurePosition, selectedFilters);
      phProductCardClick({ product, extras });
    } catch (error) {
      console.log("Error in Productcard.onProductCardClick ==>", error);
      catchErrors(error);
    };
  };

  const goToProduct = async () => {
    onProductCardClick(product);
    if(isMobile) {
      await route.push(
        `/product/${ 
          activeOption?.slug ||
          activeOption?.product?.url_key ||
          (product.slug ? product.slug : product?.url_key)
        }`
      );
      closeSideBar()
      dispatch(setProductOption({}));
    } else {
      window.open(
        `${window.location.origin}/product/${ 
          activeOption?.slug ||
          activeOption?.product?.url_key ||
          (product.slug ? product.slug : product?.url_key)
        }`,
        "_blank",
        "noopener,noreferrer"
      );
      closeVariantDrawer()
    }
    dispatch(setShowCart(false))
    if(document.body.classList.contains("active-variant-drawer")) {
      document.body.classList.remove("active-variant-drawer");
    }
    document.documentElement.style.overflow = " "; 
  };

  const handleAddToCart = async (product) => {
    try {
      setLoading(true)
      let cart = localStorage.getItem("hyuga-cart");
      const user = getCookie("user") ? JSON.parse(getCookie("user")) : null;

      if (!cart) {
        await useUpdateCart(user, dispatch, false, true);
        cart = localStorage.getItem("hyuga-cart");
      }

      let parseCart = JSON.parse(cart);

      if (!isLoggedIn && typeof parseCart.quoteId === "number") {
        await useUpdateCart(null, dispatch, true, true);
        parseCart = JSON.parse(localStorage.getItem("hyuga-cart"));
      }

      const data = prepareData(parseCart, product);
      if(!data) {
        return
      }
      if (user) data.data.customerId = user.id;

      data.data.eventId = uuidv4();

      const resp = await dispatch(addToCart(data)).unwrap();
      if (!resp.data.status) {
        setIsInitQtyAdded(false)
        let message = 'No such entity with %fieldName = %fieldValue'
        if(resp.data.message.error === message) {
          await createNewCartId(user, dispatch)
          return handleAddToCart(product)
        }
        if(!resp.data.message) {
          console.error(resp.data)
          return
        }
        setNotAvailable(true)
        return catchErrors(null, resp.data.message);
      }

      await dispatch(setShowAnimation(true))
      setTimeout(()=>{
        dispatch(setShowAnimation(false))
      },2000)
      await useUpdateCart(user, dispatch);
      await useNotify("success",'custom', dispatch, product);
      
     

      await fetchFrequentlyBought(product)
     

      const extras = {
        featureSource: featureSource,
        featureValue: featureValue,
        featurePosition: featurePosition,
        deals: product.deal_of_the_day ? "Deal of the day" : 
            product.special_to_date_formatted ? "Flash deal ": null,
        timeDeal: product.special_to_date_formatted ? getIsoTime(product.special_to_date_formatted) : null,
        ...getSelectedFilter(selectedFilters),
    };

      addToCartDataLayer(product, featureSource, featureValue, featurePosition, selectedFilters);
      addToCartTracker({ product, user });
      addToCartFbq(product, data.data.eventId);
      addToCartSnaptr(product);
      trackAddToCart({ product, user, extras });
      phAddToCart({ product, extras });
      if(document.body.classList.contains("active-variant-drawer")) {
        document.body.classList.remove("active-variant-drawer");
      }
      document.documentElement.style.overflow = ""; 
    } catch (e) {
      catchErrors(e);
    }finally{
      setLoading(false)
    }
  };

  const fetchFrequentlyBought = async (prod) => {
    try {
      setLoading(true);
      const data = {
        method: "get",
        url: `/catalog/crosssell/slug/${prod?.slug || prod?.url_key}/products`,
      };
      const resp = await requestCatalogInstance(data);

      if (!resp.data.status) {
        setLoading(false);
        setFrequentBoughtProd([]);
        return
      }
      setFrequentBoughtProd(resp?.data?.data?.data);
      dispatch(setFrequentlyBought({fbp: resp?.data?.data?.data, id: featureClass}))
      if(isMobile) {
        closeSideBar()
        // dispatch(setProductOption({}));
        if (activeOption) {
          setActiveOption(null);
        } 
      } else if(cardOnListing && resp?.data?.data?.data.length > 0 ) {
        setShowFrequentBought(true)
      } else if(frequentBoughtProd.length === 0) {
        closeSideBar()
      }
      dispatch(setProductOption(prod))
      
    } catch (e) {
      catchErrors(e);
    } finally {
      setLoading(false);
    }
  };



  const closeVariantDrawer = () => {
    closeSideBar()
    dispatch(setProductOption({}));
    if (document.body.classList.contains("active-variant-drawer")) {
      document.body.classList.remove("active-variant-drawer");
    }
    document.documentElement.style.overflow = ""; 
  };

  const handleVariant = (e, option, i=0) => {
    setOptionIndex(i ?? 0)
    setActiveOption(option)
    getEstimationTime(option?.estimated_delivery_time?.delivery_time || option?.product?.estimated_delivery_time?.delivery_time || null)
  };

  const notifyMe = (product) => {
    const activeProduct = (activeOption && activeOption.product) ? activeOption.product : (activeOption) ? activeOption : product
    dispatch(setNotifyProduct(activeProduct))
    if(isMobile){
      dispatch(setDrawer({
        type: 'notify-me-drawer',
        show: true
      }))
    }else{
      dispatch(
        setDialog({
          show: true,
          type: "notify-me",
        })
      );
    }
    // setOpenNotify(true)
    document.querySelector('html').style.overflow = 'hidden'
  }

  if((activeOption?.stock_status === false || ( activeOption?.product && activeOption?.product?.stock_status !== 'IN_STOCK'))) {
    paddingAdjust(true)
  } else {
    paddingAdjust(false)
  }

  const variantIteration = useMemo(() => {
    if(product?.variants) {
      if(product.variants[0].attributes.length > 1 && activeOption) {
        const plpVariant = product?.variants.filter((option) => option.attributes[1].label === activeOption?.attributes[1].label)
        return plpVariant
      } else {
        return product?.variants
      }
    } else if(product?.configurable_product_options) {
      if(product?.configurable_product_options.length > 1) {
        const plpVariant = product?.configurable_product_options[0].values.variants.filter((option, i) => option?.[product?.configurable_product_options[1].attribute_code] === activeOption?.[product?.configurable_product_options[1].attribute_code])
        return plpVariant
      } else {
        return product?.configurable_product_options[0].values.variants
      }
    }
  }, [product?.variants, product?.configurable_product_options, activeOption])

  useEffect(() => {
    const validQuantity = Math.max(quantity, 1);
 
    const totalPriceElement = document.querySelector('.activeOption .regular-price-container');
    const priceValue = totalPriceElement
      ? parseInt(totalPriceElement.innerText.replace(/[₹,]/g, '')) * validQuantity
      : (activeOption?.product?.price_range?.minimum_price?.final_price?.value ||
        activeOption?.special_price) * validQuantity;
  
    if (totalRef.current) {
      totalRef.current.innerText = `₹${priceValue}`;
    }
  }, [activeOption, quantity]);
  
  

  const getExactAttr = useMemo(() => {
    let key = activeOption?.product?.primary_l2_category?.slug ?? activeOption?.product?.l2_category?.slug ?? null
    if(!key) {
      return activeOption?.product?.item_weight ?? null
    }
    return activeOption?.product[getServingsAttr[key]] ? activeOption?.product[getServingsAttr[key]] : activeOption?.product.item_weight
  }, [activeOption])

  const ratingStarColor = useCallback((rating) => {
    let color = '';
    if (rating >= 4) {
      color = '#579568';
    } else if (rating >=2 && rating < 4) {
      color = '#FDB323';
    } else {
      color = '#F46464';
    }
    return color;
  }, [activeOption]);


  const handleCounterClick = (value, type) => {
    setQuantity(value);
    if (type === "plus") {
      productIncrementDataLayer(product, value);
      productIncrementFbq(product, value);
      productIncrementTracker({ product });
      trackProductIncrement({ product });
      phProductIncrement({ product });
    } else if (type === "minus") {
      productDecrementDataLayer(product, value);
      productDecrementFbq(product, value);
      productDecrementTracker({ product });
      trackProductDecrement({ product });
      phProductDecrement({ product });
    };
  };

  const minQty = () => {
    useNotify('success', 'notify-toast', null, null, `Minimum Order Quantity for this item is  ${product?.stock_info?.min_sale_qty} `)
  }

  const prepareQuantityData = (parseCart) => {
    const data = {
      quote_id: parseCart.quoteId,
      qty: quantity,
    };
    const itemOnCart = cartItems.find(
      (item) =>
        Number(item.product_id) ===
        Number(activeOption?.product?.id || activeOption?.product_id)
    );

    const url = `/cart/${type}/edit/${parseCart.quoteId}/item/${itemOnCart.item_id}`;

    const finalData = {
      cartItem: data,
    };
    if (type === "customer") {
      finalData.customerId = user.id;
    }

    return {
      method: "post",
      url: url,
      data: finalData,
    };
  };

  const getEstimationTime = (time) => {
    // const getHours = Math.floor(calculateHoursDifference(time))
    // const getEstimation = getDeliveryTime(time, getHours)
    const getEstimation = getDeliveryHours(time)
    setEstimatedTime(getEstimation)
  }



 const handleQuantityStepper = async () => {
   try {
     setLoading(true);
     const cart = localStorage.getItem("hyuga-cart");
     const parseCart = JSON.parse(cart);
     const data = prepareQuantityData(parseCart);

     const resp = await dispatch(updateCart(data)).unwrap();
     if (!resp.data.status) {
       setIsInitQtyAdded(false);
       let message = "No such entity with %fieldName = %fieldValue";
       if (resp.data.message.error === message) {
         await createNewCartId(user, dispatch);
         return handleAddToCart(product);
       }
       if (!resp.data.message) {
         console.error(resp.data);
         return;
       }
       return catchErrors(null, resp.data.message);
     }

     await useUpdateCart(user, dispatch);
     await successResponse(resp, parseCart);
     if (isMobile) {
       dispatch(
        setDrawer({
          type: 'variant-drawer',
          show: true
        })
       );
       if (activeOption) {
         setActiveOption(null);
       }
     } else {
       closeSideBar();
     }
     document.documentElement.style.overflow = "";
   } catch (e) {
     catchErrors(e);
   } finally {
     setLoading(false);
   }
 };
 

     useEffect(() => {
      const isItemOnCart = cartItems.find(item => Number(item.product_id)  === Number(activeOption?.product?.id || activeOption?.product_id))
      
      if (isItemOnCart) {
        setShowCounter(true);
        setIsInitQtyAdded(true)
        setQuantity(isItemOnCart.qty) 
      } else {
        setShowCounter(false);
        setQuantity(product.min_sale_qty || 1)
        setIsInitQtyAdded(false)
       
      }
    }, [cartItems,activeOption]);

  useEffect(()=>{
    const isItemOnCart = cartItems.find(item => Number(item.product_id)  === Number(activeOption?.product?.id || activeOption?.product_id))
      
    if (isItemOnCart) {
      setShowCounter(true);
      setQuantity(isItemOnCart.qty) 
      
    } else {
      setShowCounter(false);
      setQuantity(product.min_sale_qty || 1)
    }

  },[])
 

const handleAddingCart = ()=>{
  const isItemOnCart = cartItems.find(item => Number(item.product_id)  === Number(activeOption?.product?.id || activeOption?.product_id))
  if (isItemOnCart) {
    setShowCounter(true);
    setQuantity(isItemOnCart?.qty)

  } 
  setShowCounter(true)
  // setIsAdded(true)
  // setQuantity(1)
}

   const removeItem = async () => {
        try {
            setLoading(true);
            const user = getCookie("user") ? JSON.parse(getCookie("user")) : null;
            const itemOnCart = cartItems.find(item => Number(item.product_id)  === Number(activeOption?.product?.id || activeOption?.product_id))
            const data = {
                method: "delete",
                url: `/cart/${type}/remove/${cartState.data.quoteId}/item/${itemOnCart.item_id}`,
            };

            if (user) {
                data.url = `/cart/${type}/${user.id}/remove/${cartState.data.quoteId}/item/${itemOnCart.item_id}`;
            }

            const resp = await dispatch(removeToCart(data)).unwrap();
            if (!resp.data.status) {
                if(!resp.data.message) {
                  console.error(resp.data)
                  return
                }
                return catchErrors(null, resp.data.message);
              }
            await useUpdateCart(user, dispatch)
            successResponse(resp, product);
            removeFromCartFbq(product, featureSource, featurePosition, featureValue);
            removeFromCartDataLayer(product, user?.id, featureSource, featurePosition, featureValue);
            removeFromCartTracker({product, user, featureSource, featurePosition, featureValue});
            trackRemoveFromCart({product, user, featureSource, featurePosition, featureValue});
        } catch (e) {
            catchErrors(e);
        } finally {
            setLoading(false);
        }
    };

    const successResponse = (resp, product) => {
      const cart = localStorage.getItem("hyuga-cart");
      const parseCart = JSON.parse(cart);
      parseCart.items = parseCart.items.filter(
          (item) => item.item_id !== product.item_id
      );
      
      localStorage.setItem("hyuga-cart", JSON.stringify(parseCart));
      useNotify("success", resp.data.message);
    
  };

  const handleConfirm = () => {
    if (isInitQtyAdded) {

      handleQuantityStepper()
    } else {
      handleAddToCart(activeOption.product ?? activeOption)
    }
  }
  useEffect(() => {
    if (quantity === 0 && !isInitQtyAdded) {
      setShowCounter(false)
      return
    }
    if (quantity === 0) {
      removeItem(activeOption.product ?? activeOption)

    }
  }, [quantity])

  return (
    <div>
      {loading && <div className=""><Loader styleClass={'z-[100]'}/></div>}
      {showFrequentBought ? <div>
<FrequentlyBought close={()=>closeSideBar()}/>
      </div>:<div className="mobile-product-config-container rounded-t-xl">
      <div className={`mobile-product-configuration block text-gray-100 fixed bottom-0 bg-white pt-3 pb-[18px] w-full z-10 shadow-xl sm:max-w-[448px] ${(activeOption?.stock_status === true || activeOption?.product?.stock_status === 'IN_STOCK') ? 'border': ''}`}>
        {(activeOption?.stock_status === false || (activeOption?.product && activeOption?.product?.stock_status !== 'IN_STOCK')) &&
          <div className="px-4 mb-4 bg-[#FFF2F2] text-[#8D4747] font-medium text-base h-[30px] flex items-center">
          Select {(product?.configurable_product_options?.length > 0 && optionIndex != null) ? 
            product?.configurable_product_options[optionIndex]?.label :
            (product?.configurable_options?.length > 0 && optionIndex != null) ?
            product?.configurable_options[optionIndex]?.label :
            "an option"}:
          <span className="font-semibold pl-1">
            {activeOption?.option_title || activeOption?.attributes?.[0]?.label || "This option"} is sold out
          </span>
        </div>
        }
        <div className="flex px-4 items-center h-full">
        <div className="basis-2/5 h-[40px] items-center">
             <div className='text-base text-gray-50 font-normal'>TOTAL</div> 
               <div ref={totalRef} className='text-xl text-gray-100 font-bold'>₹{''}</div>
          </div>
          <div className="text-center w-full text-hyugapurple-500 flex justify-center items-center h-full">
            {(activeOption?.product?.stock_status === 'IN_STOCK' || activeOption?.stock_status === true) ?
            showCounter ? <div className="flex items-center gap-x-3 justify-between w-full">
              <div className="flex w-full">
                <Counter update={handleCounterClick} notAvailable={notAvailable} product={activeOption.product || activeOption} isMin={minQty} size="extra-medium" count={quantity} featureSource={featureSource} featurePosition={featurePosition} featureValue={featureValue} />
              </div>
                 <button className={`bg-hyugapurple-500 text-white rounded-xl text-[16px]/[20px] w-full px-3 sm:px-6 py-3 font-semibold transition sm:text-[14px]`}  onClick={handleConfirm}>Confirm</button>
            </div> : 
            <button
              className={`bg-hyugapurple-500 text-white rounded-xl text-[16px]/[24px] w-full px-3 sm:px-6 py-3 font-semibold transition sm:text-[14px]`}
              onClick={() => handleAddingCart()}
            >
              Add to Cart
            </button> :
            <button
            className={`bg-[#FFD481] text-[#5B3A16] rounded-xl text-[14px] w-full px-3 sm:px-6 py-3 font-semibold transition sm:text-[14px]`}
            onClick={() => notifyMe(activeOption.product ?? activeOption)}
          >
            Notify Me
          </button>
            }
          </div>
        </div>
      </div>
      <div className="variant-title fixed w-full z-10 bg-white rounded-t-xl text-gray-100 px-4 py-3.5 shadow flex justify-between items-center">
        <p className="text-lg font-semibold">
          Select{" "}
          Option{" "}
          (
          {product?.configurable_product_options
            ? product?.configurable_product_options[0]?.values?.variants?.length
            : product?.variants?.length}
          )
        </p>
        <XIcon className="h-5 w-5" onClick={closeVariantDrawer} />
      </div>
      <div className="variant-info px-4 relative sm:pb-0 pt-12 pb-[70px] max-h-[75vh] overflow-y-auto">
        <div className="variant-product pt-4">
        <div className="flex gap-2 border p-3 rounded-lg">
            <div className="basis-3/12 order-2 rounded-lg border max-w-[110px] max-h-[100px] flex items-center justify-center p-2" onClick={goToProduct}>
              {activeOption ? (
                <img
                  src={`${compressImage(
                    activeOption?.product?.image.url ||
                    publicRuntimeConfig.imageUrl + activeOption.image, 100, 140)
                  }`}
                  height={140}
                  width={100}
                  className="w-[100px] h-[90px] cursor-pointer activeoption rounded-lg"
                  onError={({ currentTarget }) => {
                    currentTarget.onerror = null; // prevents looping
                    currentTarget.src="/assets/images/asset-placeholder.jpeg";
                  }}
                ></img>
              ) : product?.image?.url ? (
                <img
                  src={`${compressImage(product?.image?.url, 100, 140)}`}
                  height={140}
                  width={100}
                  className="w-[100px] h-[90px] cursor-pointer product"
                  onError={({ currentTarget }) => {
                    currentTarget.onerror = null;
                    currentTarget.src="/assets/images/asset-placeholder.jpeg";
                  }}
                ></img>
              ) : (
                <img
                  src={`${publicRuntimeConfig.imageUrl}${product.image}`}
                  className="w-[100px] cursor-pointer product"
                  height={140}
                  width={100}
                  onError={({ currentTarget }) => {
                    currentTarget.onerror = null;
                    currentTarget.src="/assets/images/asset-placeholder.jpeg";
                  }}
                ></img>
              )}
            </div>
            <div className="basis-9/12 cursor-pointer">
              <p
                onClick={goToProduct}
                className="line-clamp-2 text-base/[20px] text-gray-100 font-normal sm:text-base/[20px]"
              >
                {activeOption?.name ||
                  activeOption?.product?.name ||
                  product.name}
              </p>
              <p className="text-[#686E73] text-sm/[18px] sm:text-base/[20px] font-normal">{activeOption?.flavour ||
                  activeOption?.product?.flavour ||
                  product.flavour}  {(getExactAttr && activeOption.product?.flavour) ? (
                    <span className="px-[3px]">{" |"}</span>
                  ): ''}{" "} {getExactAttr && <span>{getExactAttr}</span>}</p>
              <div className="mt-1.5 flex gap-x-[2px] items-center">
                {product?.star_ratings || product?.ratings?.stars ? (
                  <>
                    <span className="text-sm text-gray-100 font-semibold">
                      {product?.ratings?.stars || product?.star_ratings}
                    </span>
                    <StarIcon
                      className={`flex-shrink-0  h-4 w-4`}
                      style={{
                   color: `${ratingStarColor(product.ratings?.stars ||product?.star_ratings )}`
                      }}

                      />
                   {" "}
                  </>
                ) : null}

                {product?.ratings?.count || product?.review_count ? (
                  <span className="text-sm text-[#8B8B95] font-medium">
                    ({product?.ratings?.count || product?.review_count})
                  </span>
                ) : null}
              </div>
              <div className="product-link flex items-center text-sm pt-2 sm:cursor-pointer" onClick={goToProduct}>
                <p className="font-semibold">View product</p>
                <ChevronRightIcon className="w-3 h-3 relative top-[.5px]" />
              </div>
            </div>
          </div>
          {/* <div className="bg-[#F4F4F9] w-full h-[2px]" /> */}
           <div className="varaint-container">
            {/** start of PLP variant */}
            {product?.variants ? <div className="plp-variant">
              {(product?.variants && product?.variants.length !== 0 && product?.variants[0].attributes?.length !== 0) ? (
                <div className={`overflow-hidden flex flex-col ${product?.variants[0].attributes?.length > 1 ? 'first-variant' : ''}`}>
                  <div className="font-normal text-base pt-4 pb-[10px] flex items-center justify-between text-[#686E73] flex-wrap">
                    <div>
                      Select {product?.configurable_options[0].label}:
                      <span className=" pl-1 text-gray-100 text-base font-bold">
                        {activeOption?.product?.[product?.variants[0].attributes[0].code] ?
                          activeOption?.product?.[product?.variants[0].attributes[0].code] :
                          ''
                        }</span>
                    </div>
                    {(estimatedTime && (activeOption?.product?.stock_status === 'IN_STOCK' || activeOption?.product?.stock_info?.is_in_stock)) ? <DeliveryBadge estimatedTime={estimatedTime} /> : ''}
                  </div>
                  <ResponsiveLayout isMobile={isMobile}>
                    {variantIteration?.map((option, index) => (
                      <div key={index} className={`${option?.product?.stock_status === 'IN_STOCK' ? 'cursor-pointer text-gray-100' : '!bg-[#ededee] !text-[#8B8B95]'} border slider-horizontal-scroll rounded-md flex ${activeOption?.attributes[0].value_index == option?.attributes[0].value_index ? 'bg-primary text-white border-primary' : (product?.variants[0].attributes.length > 1) ? 'bg-white' : "bg-[#F4F4F9] mb-4"} bg-[#F4F4F9] border-[#D6D6E2]`}>
                        {(option?.attributes.length !== 0 && option?.attributes[0]) ? <VariantSelection
                          type={"radio"}
                          activeOption={option}
                          label={option?.attributes[0].label}
                          style={"w-4 h-4 hidden"}
                          isMultipleVariant={product?.variants[0].attributes.length > 1}
                          checked={
                            activeOption?.attributes[0].value_index ==
                            option?.attributes[0].value_index
                          }
                          // disabled={option?.product.stock_status === 'OUT_OF_STOCK'}
                          labelStyle={`font-semibold min-w-[60px] max-w-[188px] relative top-0 text-inherit w-full pl-3 pr-3 py-1.5 block`}
                          name={option?.attributes[0].code}
                          id={option?.attributes[0].label}
                          value={option?.attributes[0].value_index}
                          onChange={(e) => handleVariant(e, option)}
                        /> : null}
                      </div>
                    ))}
                  </ResponsiveLayout>
                </div>
              ) : null}
                {((product?.variants && product?.variants[0].attributes?.length > 1)) && <div className="bg-[#F4F4F9] w-full h-[2px] my-[14px]" />}
              {(product?.variants && product?.variants.length !== 0 && product?.variants[0].attributes?.length > 1) ? (
                <div className="overflow-hidden flex flex-col second-variant">
                  <div className="font-normal text-base pb-[10px] flex items-center text-[#686E73]">Select {product?.configurable_options[1].label}:
                    <span className=" pl-1 text-gray-100 text-base font-bold">
                      {activeOption?.product?.[product?.variants[0].attributes[1].code] ?
                        activeOption?.product?.[product?.variants[0].attributes[1].code] :
                        ''
                      }</span>
                  </div>
                  <ResponsiveLayout isMobile={isMobile}>
                    {product.variants.filter((option) => option.attributes[0].label === activeOption?.attributes[0].label).map((option, index) => (
                      <div key={index} className={`${option?.product?.stock_status === 'IN_STOCK' ? 'cursor-pointer text-gray-100' : '!bg-[#ededee] !text-[#8B8B95]'} border slider-horizontal-scroll rounded-md mb-4 flex ${activeOption?.attributes[1].value_index == option?.attributes[1].value_index ? 'bg-primary text-white border-primary' : 'bg-[#F4F4F9] border-[#D6D6E2]'}`}>
                        {(option?.attributes.length !== 0 && option?.attributes[1]) ? <VariantSelection
                          type={"radio"}
                          activeOption={option}
                          label={option?.attributes[1].label}
                          style={"w-4 h-4 hidden"}
                          checked={
                            activeOption?.attributes[1].value_index ==
                            option?.attributes[1].value_index
                          }
                          // disabled={option?.product.stock_status === 'OUT_OF_STOCK'}
                          labelStyle={`font-semibold relative top-0 text-inherit w-full pl-3 pr-3 py-1.5 block ${option?.product?.stock_status === 'IN_STOCK' ? '' : '!text-[#8B8B95]'} ${activeOption?.attributes[1].value_index == option?.attributes[1].value_index && 'selected-flavour'}`}
                          name={option?.attributes[1].code}
                          id={option?.attributes[1].label}
                          value={option?.attributes[1].value_index}
                          onChange={(e) => handleVariant(e, option,1)}
                        /> : null}
                      </div>
                    ))}
                  </ResponsiveLayout>
                </div>
              ) : null}
            </div> : (product?.configurable_product_options) ?
            <div className="slider-variant">
              {
                product?.configurable_product_options ? (
                <div className={`overflow-hidden flex flex-col ${product?.configurable_product_options?.length > 1 ? 'first-variant' : ''}`}>
                        <div className="font-normal text-base pt-4 pb-[10px] flex items-center justify-between text-[#686E73] flex-wrap">
                          <div>
                            Select {(product?.configurable_product_options && product?.configurable_product_options.length !== 0) ? product?.configurable_product_options[0].label : product?.configurable_options[0].label}:
                            <span className=" pl-1 text-gray-100 text-base font-bold">
                              {product?.configurable_product_options.length > 1 ? activeOption?.[product.configurable_product_options[0].attribute_code] : (activeOption?.option_title) ?
                                activeOption?.option_title :
                                ''
                              }</span>
                          </div>
                          {(estimatedTime && (activeOption?.stock_status === 'IN_STOCK' || activeOption?.stock_info?.is_in_stock)) ? <DeliveryBadge estimatedTime={estimatedTime} />: ''}
                        </div>
                        <ResponsiveLayout isMobile={isMobile}>
                          {variantIteration?.map(
                            (option, index) => (
                              <div
                                key={index}
                                className={`${option?.stock_info?.is_in_stock ? 'cursor-pointer text-gray-100' : '!bg-[#ededee] !text-[#8B8B95]'} border slider-horizontal-scroll rounded-md flex ${activeOption?.sku === option.sku ? 'bg-primary text-white border-primary activeOption' : (product?.configurable_product_options.length > 1) ? 'bg-white' : "bg-[#F4F4F9] mb-4"} border-[#D6D6E2]`}
                              >
                                <VariantSelection
                                  type={"radio"}
                                  activeOption={option}
                                  label={option.option_title}
                                  style={"w-4 h-4 hidden"}
                                  isMultipleVariant={product?.configurable_product_options.length > 1}
                                  // disabled={!option.stock_status}
                                  checked={activeOption?.sku === option.sku}
                                  labelStyle={`font-semibold min-w-[60px] max-w-[188px] relative top-0 text-inherit w-full pl-3 pr-3 py-1.5 block`}
                                  name={product?.configurable_product_options[0].label}
                                  id={option.option_title}
                                  value={option.value_index}
                                  onChange={(e) => handleVariant(e, option)}
                                />
                              </div>
                            )
                          )}
                        </ResponsiveLayout>
                </div>
              ) : null}
                {(product?.configurable_product_options && product?.configurable_product_options.length > 1) && <div className="bg-[#F4F4F9] w-full h-[2px] my-[14px]" />}
              {
                (product?.configurable_product_options && product?.configurable_product_options.length > 1) ? (
                <div className="overflow-hidden flex flex-col second-variant">
                  <div className="font-normal text-base pb-[10px] flex items-center text-[#686E73]">Select {(product?.configurable_product_options && product?.configurable_product_options.length > 1) ? product?.configurable_product_options[1].label : product?.configurable_options[1].label}: 
                  <span className=" pl-1 text-gray-100 text-base font-bold">
                    {activeOption?.[product.configurable_product_options[1].attribute_code]}
                  </span>
                  </div>
                  <ResponsiveLayout isMobile={isMobile}>
                  {product?.configurable_product_options[1]?.values?.variants?.filter((option) => option[product?.configurable_product_options[0].attribute_code] === activeOption?.[product?.configurable_product_options[0].attribute_code]).map(
                      (option, index) => (
                        <div
                          key={index}
                          className={`${option?.stock_info?.is_in_stock ? 'cursor-pointer text-gray-100' : '!bg-[#ededee] !text-[#8B8B95]'} border slider-horizontal-scroll rounded-md mb-4 flex ${activeOption?.sku === option.sku ? 'bg-primary text-white border-primary activeOption' :  "bg-[#F4F4F9] border-[#D6D6E2]"}`}
                        >
                          <VariantSelection
                            type={"radio"}
                            activeOption={option}
                            label={option.option_title}
                            style={"w-4 h-4 hidden"}
                            // disabled={!option.stock_status}
                            checked={activeOption?.sku === option.sku}
                            labelStyle={`font-semibold relative top-0 text-inherit w-full pl-3 pr-3 py-1.5 block ${option?.stock_info?.is_in_stock ? '' : '!text-[#8B8B95]'} ${activeOption?.sku === option.sku ? 'selected-flavour ': ''}`}
                            name={product.configurable_product_options[1].label}
                            id={option.option_title}
                            value={option.value_index}
                            onChange={(e) => handleVariant(e, option, 1)}
                          />
                        </div>
                      )
                    )}
                  </ResponsiveLayout>
                </div>
              ) : null}
            </div> : ''}
          </div>
        </div>
      </div>
    </div>}
    </div>
    
  );
};

export default ProdConfig;


const ResponsiveLayout = ({ children, isMobile }) => {
  return (
    <>
    {
      isMobile ? <div className="product-options horizontal-scroll flex items-start overflow-scroll p-2 pl-0 gap-2">
      {children}
    </div>: <Slider className="slick-variant" {...variantSliderSettings}>
        {children}
      </Slider>
    }
    </>
  )
}