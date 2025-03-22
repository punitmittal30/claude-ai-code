import { XIcon } from "@heroicons/react/outline";
import React, { Fragment } from "react";
import { Dialog, Transition } from "@headlessui/react";
import useResize from 'hooks/useResize';
const FitnessWidgetInfo = ({ close }) => {

    const screen = useResize();
    return (
        screen.device == "mobile"
            ? <div>
                <div className="w-full pl-4 pt-4 pb-3 text-lg text-gray-100 border-b font-semibold flex justify-between">
                    <span>BMI Score Calculator</span>
                    <XIcon
                        onClick={(e) => close(e)}
                        className="h-6 w-6 text-gray-100 mr-2 pr-1 cursor-pointer"
                        size={12}
                        aria-hidden="true"
                    />
                </div>
                <div className='w-fit px-4 pt-3.5 pb-6'>
                    <div className="flex-1 overflow-y-auto mt-[1px] mb-4">
                        <p className="font-normal text-[#424749] text-base leading-[22px] p-3 text-left">{"The body mass index (BMI) is a measure that uses your height and weight to work out if your weight is healthy."}</p>
                        <img src="/assets/images/BMICalculator.svg" className="ml-3 w-full pr-6"></img>
                        <p className="font-semibold text-base text-[#424749] leading-[22px] text-left p-3">{"How to calculate my BMI score?"}</p>
                        <img src="/assets/images/BMIFormula.svg" className="ml-3 w-full pr-6"></img>
                    </div>
                </div>
            </div>
            : <div>
                <Transition.Root show={true} as={Fragment}>
                    <Dialog as="div" className="relative z-[999]" onClose={close}>
                        <Transition.Child
                            as={Fragment}
                            enter="ease-in-out duration-500"
                            enterFrom="opacity-0"
                            enterTo="opacity-100"
                            leave="ease-in-out duration-500"
                            leaveFrom="opacity-100"
                            leaveTo="opacity-0"
                        >
                            <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
                        </Transition.Child>

                        <div className="fixed inset-0 overflow-hidden">
                            <div className="absolute inset-0 overflow-hidden">
                                <div className="pointer-events-none fixed inset-y-0 right-0 flex max-w-full mobile-w">
                                    <Transition.Child
                                        as={Fragment}
                                        enter="transform transition ease-in-out duration-500 sm:duration-700"
                                        enterFrom="translate-x-full"
                                        enterTo="translate-x-0"
                                        leave="transform transition ease-in-out duration-500 sm:duration-700"
                                        leaveFrom="translate-x-0"
                                        leaveTo="translate-x-full"
                                    >
                                        <Dialog.Panel className="pointer-events-auto w-screen max-w-md mobile-w">
                                            <div className="bg-white shadow-xl h-full flex flex-col">
                                                <div className="flex items-center justify-between py-4 px-4 shadow-md">
                                                    <Dialog.Title
                                                        className="text-xl font-semibold text-gray-100 flex items-center">{`BMI Score`}
                                                    </Dialog.Title>

                                                    {screen.device == "mobile" ? (
                                                        <XIcon
                                                            onClick={(e) => close(e)}
                                                            className="h-7 w-7 text-gray-100 pl-2 cursor-pointer"
                                                            size={12}
                                                            aria-hidden="true"
                                                        />
                                                    ) : (
                                                        <div className="flex h-7 items-center">
                                                            <button
                                                                type="button"
                                                                className="-m-2 p-2 text-gray-400 hover:text-gray-500 outline-0"
                                                                onClick={(e) => close(e)}
                                                            >
                                                                <XIcon
                                                                    className="h-6 w-6 text-gray-100 hidden sm:block"
                                                                    aria-hidden="true"
                                                                />
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="flex-1 overflow-y-auto mt-[1px] mb-4">
                                                    <p className="font-normal text-[#424749] text-base leading-[22px] p-3 text-left">{"The body mass index (BMI) is a measure that uses your height and weight to work out if your weight is healthy."}</p>
                                                    <img src="/assets/images/BMICalculator.svg" className="ml-3 w-full pr-6"></img>
                                                    <p className="font-semibold text-base text-[#424749] leading-[22px] text-left p-3">{"How to calculate my BMI score?"}</p>
                                                    <img src="/assets/images/BMIFormula.svg" className="ml-3 w-full pr-6"></img>
                                                </div>
                                            </div>
                                        </Dialog.Panel>
                                    </Transition.Child>
                                </div>
                            </div>
                        </div>
                    </Dialog>
                </Transition.Root>
            </div>
    )
};

export default FitnessWidgetInfo;
