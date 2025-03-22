import React, { useEffect } from "react";
import WithCustomHocSlider from "shared/components/CustomSlider";
import { requestContentInstance, requestCategoryInstance } from "service";
import { useState,useRef } from "react";
import { catchErrors } from "utils/index";
import useResize from "hooks/useResize";
import Slider from "@ant-design/react-slick";
import { blogSliderSettings } from "shared/components/CustomSlider/constants";
import { useDispatch, useSelector } from "react-redux";
import {
  setFilterValues,
  setSelectedFilter,
  setEmptyFilter,
  
} from "../redux/reducer";
import { useRouter } from "next/router";
import { bestDealscategordIds } from "./constants";
const BubblesFilter = ({ data, filters }) => {
  const dispatch = useDispatch();
  const { isMobile } = useResize();
  const [bubbleContent, setBubbleContent] = useState([]);
  const [selectedBubble, setSelectedBubble] = useState(null);
  const selectedFilters = useSelector((state) => state.category.selectedFilter);
  const lastClickedBubble = useRef(null);
  const router = useRouter();
  const isHeaderVisible = useSelector((state)=>state.category.showHeaderOnOfferpage)
  useEffect(() => {
    if (window.innerWidth < 475) {
      setBubbleContent(data?.data?.banners?.m_web);
    } else {
      setBubbleContent(data?.data?.banners?.web);
    }
    if (data) {
      handleCategoryId();
    }
    setSelectedBubble(0)
  }, [data]);

  const handleCategoryId = async () => {
    try {
      const requestData = {
        method: "get",
        url: "/search/id-slug-mapping",
      };
      const resp = await requestCategoryInstance(requestData);
      if (!resp.data.status) {
        return;
      }
      const categoryIdData = resp?.data.data.map_by_slug;
      Object.assign(bestDealscategordIds, categoryIdData);
    } catch (e) {
      catchErrors(e);
    }
  };

  const handleBubbleClick = (url,i) => {
   if (Object.keys(filters).length > 0) {
      dispatch(setEmptyFilter());
    } 
     lastClickedBubble.current = i;
    setTimeout(() => {
      const element = document.getElementById("category-filter");
      if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }, 0);

    if (
      new URL(url).pathname === "/product-category/offer-zone" &&
      !url.includes("?")
    ) {
      return;
    } else if (url?.includes("?")) {
      const queryParams = new URLSearchParams(new URL(url).search);
            const selectedOption = {
        field: queryParams.get("offers") ? "offers" : "badges",
        value: queryParams.get("offers") ? queryParams.get("offers") : queryParams.get("badges"),
      };
      dispatch(setFilterValues(selectedOption));
      dispatch(setSelectedFilter(selectedOption));
    } else {
      const splitUrl = url?.split("/");
      const categoryName = splitUrl[splitUrl?.length - 1];
      const categoryValue = bestDealscategordIds[categoryName];
      const selectedOption = {
        field: "primary_l2_category",
        value: categoryValue,
      };
      dispatch(setFilterValues(selectedOption));
      dispatch(setSelectedFilter(selectedOption));
    }
  };

 
 
  useEffect(() => {
   if (bubbleContent && bubbleContent.length > 0) {
      if (selectedFilters.length > 0) {
        const matchedIndex = bubbleContent.findIndex((bubble) =>
          selectedFilters.some((filter) =>
            filter?.label?.toLowerCase().includes(bubble?.title?.toLowerCase())
            
          )
        );

        if (matchedIndex !== -1) {
          setSelectedBubble(matchedIndex);
        } else {
          setSelectedBubble(lastClickedBubble.current)
        }
      } else {
        if(lastClickedBubble.current === null || router.asPath.includes('offer-zone')){
          setSelectedBubble(0)
        }else{
          setSelectedBubble(lastClickedBubble.current)
        }
  
      }
    }
  }, [bubbleContent, selectedFilters]);


  const settings = { ...blogSliderSettings, slidesToShow: 7.2 };

  return (
    <>


      {isMobile && bubbleContent?.length > 0 ? (
        <div
          className={`BubblesFilter sticky  z-[2] bg-[#4F4070] ${isHeaderVisible ? 'top-[94px] transition-all duration-100 ease-out':'top-[0px] transition-all duration-75 ease-in'}  border-b   pt-6`}
          id="bubble-filter"
        >
          
          <div className="horizontal-scroll flex overflow-x-scroll overflow-y-hidden ">
            {bubbleContent
              ?.sort((a, b) => a.priority - b.priority)
              .map((bubble, index) => (
                <div
                  key={index}
                  className={`flex flex-col items-center pl-4  ${
                    index === bubbleContent.length - 1 ? "pr-4":''
                  }`}
                  onClick={() =>
                    handleBubbleClick(bubble.action_url,index)
                  }
                >
                  <div className="flex justify-center items-center rounded-full w-[78px] h-[78px]   bg-gradient-to-r from-[#FF5E65] to-[#FCC85A]">
                    <img
                      src={bubble.image_url}
                      className="w-[74px] h-[74px] rounded-full  "
                    />
                  </div>
                  <p className="text-sm text-white line-clamp-2 overflow-hidden font-semibold h-8 w-[72px]  text-center mt-2">
                    {bubble.title}
                  </p>

                  {index === selectedBubble ? (
                    <div
                      className="h-[3px] w-[64px] rounded-t-[4px] mt-[14px] "
                      style={{
                        background:
                          "var(--Linear, linear-gradient(90deg, #FF5E65 0%, #FCC85A 100%))",
                      }}
                    />
                  ) : (
                    <div className="h-[3px] w-[64px] rounded-t-[4px] mt-[14px]" />
                  )}
                </div>
              ))}
          </div>
        </div>
      ) : !isMobile && bubbleContent?.length > 0 ? (
        <div className="BubblesFilter cursor-pointer border-b bg-[#4F4070] rounded-xl  pt-6 my-3">
          <div className="flex items-center justify-center mb-12">
            <img src="/assets/images/topCategoryOfferPage.svg" />
          </div>
          <Slider {...settings}>
            {bubbleContent
              ?.sort((a, b) => a.priority - b.priority)
              .map((bubble, index) => (
                <div
                  key={index}
                  className="flex flex-col items-center justify-center mx-6 "
                  onClick={() => handleBubbleClick(bubble.action_url, index)}
                >
                  <div className="flex justify-center items-center rounded-full ml-3.5 w-[140px] h-[140px]   bg-gradient-to-r from-[#FF5E65] to-[#FCC85A]">
                    <img
                      src={bubble.image_url}
                      className="w-[136px] h-[136px] rounded-full  "
                    />
                  </div>

                  <div className="w-full flex items-start justify-center">
                    <p className="text-base text-white text-center font-semibold h-[40px] line-clamp-2 w-[120px] mt-2">
                      {bubble.title}
                    </p>
                  </div>
                  
          {index === selectedBubble ? (
                    <div
                      className="h-[4px] w-full flex rounded-t-[4px] mt-[16px]"
                      style={{
                        background:
                          "var(--Linear, linear-gradient(90deg, #FF5E65 0%, #FCC85A 100%))",
                      }}
                    />
                  ) : (
                    <div className="h-[5px] w-full rounded-t-[4px] mt-[14px]" />
                  )}
                </div>
              ))}
          </Slider>
        </div>
      ):''}
     
    </>
  );
};

export default WithCustomHocSlider(
  BubblesFilter,
  "/content/banners/offer-zone/categories-bubbles",
  requestContentInstance
);
